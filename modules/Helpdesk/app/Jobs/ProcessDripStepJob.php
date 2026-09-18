<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Campaigns\DripExecution;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Services\OutboundMessageService;

class ProcessDripStepJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        private readonly DripExecution $execution,
    ) {
        $this->onQueue('drip');
    }

    public function handle(OutboundMessageService $outbound): void
    {
        $execution = $this->execution->fresh();

        if (! $execution || ! $execution->isActive()) {
            return;
        }

        $campaign = $execution->campaign()->with('steps')->first();

        if (! $campaign) {
            return;
        }

        $steps = $campaign->steps->sortBy('step_order')->values();
        $step = $steps->get($execution->current_step);

        if (! $step) {
            $execution->complete();

            return;
        }

        $customer = $execution->customer;

        $this->sendStep($execution, $campaign, $step, $customer, $outbound);

        $execution->advance();
        $execution->refresh();

        $nextStep = $steps->get($execution->current_step);

        if (! $nextStep) {
            $execution->complete();

            return;
        }

        static::dispatch($execution->fresh())
            ->delay(now()->addMinutes($nextStep->delay_minutes));
    }

    private function sendStep(
        DripExecution $execution,
        mixed $campaign,
        mixed $step,
        mixed $customer,
        OutboundMessageService $outbound
    ): void {
        if (! $customer) {
            Log::warning('ProcessDripStepJob: no customer found for execution', [
                'execution_id' => $execution->id,
                'step_id' => $step->id,
            ]);

            return;
        }

        $channel = $step->channel ?? 'web';

        if ($channel === 'email') {
            $this->sendEmail($customer, $campaign, $step, $execution);

            return;
        }

        $conversation = Conversation::query()
            ->where('customer_id', $customer->id)
            ->where('channel', $channel)
            ->latest()
            ->first();

        if (! $conversation) {
            Log::warning('ProcessDripStepJob: no conversation found for customer on channel', [
                'execution_id' => $execution->id,
                'customer_id' => $customer->id,
                'channel' => $channel,
            ]);

            return;
        }

        $outbound->sendReply($conversation, $step->body ?? '');
    }

    private function sendEmail(mixed $customer, mixed $campaign, mixed $step, DripExecution $execution): void
    {
        if (! $customer->email) {
            Log::warning('ProcessDripStepJob: customer has no email address', [
                'execution_id' => $execution->id,
                'customer_id' => $customer->id,
            ]);

            return;
        }

        $subject = $campaign->name;
        $body = $step->body ?? '';

        Mail::html($body, function ($mail) use ($customer, $subject) {
            $mail->to($customer->email)->subject($subject);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDripStepJob: job failed', [
            'execution_id' => $this->execution->id,
            'campaign_id' => $this->execution->campaign_id,
            'customer_id' => $this->execution->customer_id,
            'current_step' => $this->execution->current_step,
            'error' => $exception->getMessage(),
        ]);
    }
}
