<?php

namespace Modules\Campaign\Jobs;

use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Events\CampaignMessageSent;
use Modules\Campaign\Library\CampaignRateTracker;
use Modules\Campaign\Library\Tool;
use Modules\Campaign\Models\Subscriber;
use Modules\Campaign\Services\CircuitBreaker;
use Modules\Campaign\Services\DomainThrottler;
use Modules\Campaign\Services\SendRetryPolicy;
use Modules\CampaignSendingServers\Library\Exception\OutOfCredits;
use Modules\CampaignSendingServers\Library\Exception\RateLimitExceeded;
use Modules\CampaignSendingServers\Models\SendingServer;
use Throwable;

class SendMessage implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 600;

    public $maxExceptions = 1; // This is required if retryUntil is used, otherwise, the default value is 255

    public $failOnTimeout = true;

    // $tries is no longer needed (or effective) due to the retryUntil() method
    // public $tries = 1;

    protected $subscriber;

    protected $server;

    protected $campaign;

    protected $triggerId;

    protected $stopOnError = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($campaign, Subscriber $subscriber, SendingServer $server, $triggerId = null)
    {
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;
        $this->server = $server;
        $this->triggerId = $triggerId;
    }

    public function setStopOnError($value)
    {
        if (! is_bool($value)) {
            throw new Exception('Parameter passed to setStopOnError must be bool');
        }

        $this->stopOnError = $value;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addHours(12);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Remember that this job may not belong to a batch
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        $this->send();
    }

    // Use a dedicated method with no dependency for easy testing
    public function send($exceptionCallback = null)
    {
        $logger = $this->campaign->logger();
        $email = $this->subscriber->getEmail();
        $startTime = microtime(true);

        $logContext = [
            'campaign_uid' => $this->campaign->uid,
            'subscriber_uid' => $this->subscriber->uid,
            'server_uid' => $this->server->uid,
            'email' => $email,
        ];

        try {
            [$message, $msgId] = $this->campaign->prepareEmail($this->subscriber, $this->server, $fromCache = true);
            $logContext['message_id'] = $msgId;

            $logger->info(sprintf('Sending to %s [Server "%s"]', $email, $this->server->name));
            Log::info('Campaign email sending started', $logContext);

            // Circuit breaker: si el servidor está en fallback, saltar
            $circuit = new CircuitBreaker;
            if ($circuit->isOpen($this->server->id)) {
                throw new RateLimitExceeded('Circuit breaker open for server '.$this->server->name, 300);
            }

            // Throttle por dominio destino
            (new DomainThrottler)->throttle($email);

            $rateTrackers = [
                $this->server->getRateLimitTracker(),
                CampaignRateTracker::forCampaign($this->campaign),
            ];
            $creditTrackers = [];

            Tool::executeWithLimits($rateTrackers, null, $creditTrackers, function () use ($message, $logger, $msgId, $email, $startTime, $logContext) {
                if (config('custom.dryrun')) {
                    $sent = $this->server->dryrun($message);
                } else {
                    $sent = $this->server->send($message);
                }

                $durationMs = (int) ((microtime(true) - $startTime) * 1000);
                $logContext['duration_ms'] = $durationMs;
                $logContext['runtime_message_id'] = $sent['runtime_message_id'] ?? null;

                $trackingLog = $this->campaign->trackMessage($sent, $this->subscriber, $this->server, $msgId, $this->triggerId);
                CampaignRateTracker::forCampaign($this->campaign)->increment();

                // Emitir evento para métricas materializadas
                CampaignMessageSent::dispatch($this->campaign, $trackingLog);

                $logger->info(sprintf('Sent to %s [Server "%s"]', $email, $this->server->name));
                Log::info('Campaign email sent successfully', $logContext);
            });
        } catch (RateLimitExceeded $ex) {
            if (! is_null($exceptionCallback)) {
                return $exceptionCallback($ex);
            }

            // Retry inteligente: usar el tiempo de reset del rate limiter
            $retryAfter = max(10, (int) $ex->getRetryAfter());
            $logger->warning(sprintf('Delay [%s] for %d seconds: %s', $email, $retryAfter, $ex->getMessage()));
            Log::warning('Campaign email rate limited', array_merge($logContext, ['retry_after_seconds' => $retryAfter]));

            $this->release($retryAfter);
        } catch (OutOfCredits $ex) {
            if (! is_null($exceptionCallback)) {
                return $exceptionCallback($ex);
            }

            $circuit->recordFailure($this->server->id);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $logContext['duration_ms'] = $durationMs;
            $logger->error(sprintf('Out of credits [%s]: %s', $email, $ex->getMessage()));
            Log::error('Campaign email out of credits', array_merge($logContext, ['error' => $ex->getMessage()]));

            if ($this->stopOnError) {
                throw $ex;
            }

            $this->campaign->trackMessage(['status' => 'failed', 'error' => $ex->getMessage()], $this->subscriber, $this->server, $msgId ?? null, $this->triggerId);
        } catch (Throwable $ex) {
            if (! is_null($exceptionCallback)) {
                return $exceptionCallback($ex);
            }

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $logContext['duration_ms'] = $durationMs;

            $retryPolicy = new SendRetryPolicy;
            $shouldRetry = $retryPolicy->shouldRetry($ex);
            $retryDelay = $retryPolicy->getDelay($ex);

            if (! $shouldRetry) {
                $circuit->recordFailure($this->server->id);
            }

            $message = sprintf('Error sending to [%s]. Error: %s', $email, $ex->getMessage());
            $logger->error($message);
            Log::error('Campaign email sending failed', array_merge($logContext, [
                'error' => $ex->getMessage(),
                'should_retry' => $shouldRetry,
                'retry_delay' => $retryDelay,
            ]));

            if ($this->stopOnError) {
                throw $ex;
            }

            if (! isset($msgId)) {
                $msgId = null;
            }

            $this->campaign->trackMessage(['status' => 'failed', 'error' => $ex->getMessage()], $this->subscriber, $this->server, $msgId, $this->triggerId);

            if ($shouldRetry && $retryDelay > 0) {
                $this->release($retryDelay);
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        // La mayoría de fallos ya se registran dentro de send() (rate limit,
        // sin crédito, error genérico con reintento); esto cubre el caso
        // terminal que no pasaba por ningún log: retryUntil() agotado (12h) o
        // una excepción relanzada con stopOnError=true.
        Log::error('SendMessage failed permanently', [
            'campaign_uid' => $this->campaign->uid ?? null,
            'subscriber_uid' => $this->subscriber->uid ?? null,
            'server_uid' => $this->server->uid ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
