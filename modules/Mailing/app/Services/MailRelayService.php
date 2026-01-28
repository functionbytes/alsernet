<?php

namespace Modules\Mailrelay\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\CampaignAnalytics;
use Modules\Mailrelay\Entities\MailrelayGroup;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Exceptions\MailrelayException;

/**
 * MailRelay Service
 *
 * Main service for interacting with the Mailrelay API.
 * Handles subscribers, groups, campaigns, analytics, webhooks, and more.
 *
 * @see FASE 6 in IMPLEMENTACION_LARAVEL_EMAIL_VALIDATOR.md
 */
class MailRelayService
{
    protected Client $client;

    protected ?string $apiKey;

    protected ?string $apiUrl;

    protected int $timeout;

    protected int $connectTimeout;

    protected array $retryConfig;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client;
        $this->apiKey = config('mailrelay.api_key');
        $this->apiUrl = config('mailrelay.api_url');
        $this->timeout = config('mailrelay.timeout', 30);
        $this->connectTimeout = config('mailrelay.connect_timeout', 10);
        $this->retryConfig = config('mailrelay.retry', [
            'max_attempts' => 3,
            'delay' => 100,
            'multiplier' => 2,
        ]);
    }

    /**
     * Make an HTTP request to the Mailrelay API
     *
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE)
     * @param  string  $endpoint  API endpoint
     * @param  array  $data  Request data
     * @param  array  $options  Additional options
     * @return array API response
     *
     * @throws MailrelayException
     */
    public function request(string $method, string $endpoint, array $data = [], array $options = []): array
    {
        $url = rtrim($this->apiUrl, '/').'/'.ltrim($endpoint, '/');

        $requestOptions = array_merge([
            'headers' => [
                'X-AUTH-TOKEN' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ], $options);

        if (! empty($data)) {
            if (strtoupper($method) === 'GET') {
                $requestOptions['query'] = $data;
            } else {
                $requestOptions['json'] = $data;
            }
        }

        $attempt = 0;
        $delay = $this->retryConfig['delay'];

        while ($attempt < $this->retryConfig['max_attempts']) {
            try {
                $response = $this->client->request($method, $url, $requestOptions);

                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();
                $result = json_decode($body, true);

                if ($statusCode >= 200 && $statusCode < 300) {
                    if (config('mailrelay.logging.enabled')) {
                        Log::channel(config('mailrelay.logging.channel', 'stack'))
                            ->info('Mailrelay API request successful', [
                                'method' => $method,
                                'endpoint' => $endpoint,
                                'status' => $statusCode,
                            ]);
                    }

                    return $result ?? ['success' => true];
                }

                throw new \Exception("API returned status code {$statusCode}: {$body}");
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $attempt++;

                if ($attempt >= $this->retryConfig['max_attempts']) {
                    $message = $e->getMessage();
                    if ($e->hasResponse()) {
                        $message .= ' - '.$e->getResponse()->getBody()->getContents();
                    }

                    Log::error('Mailrelay API request failed after retries', [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'error' => $message,
                        'attempts' => $attempt,
                    ]);

                    throw MailrelayException::connectionFailed($message);
                }

                usleep($delay * 1000);
                $delay *= $this->retryConfig['multiplier'];
            } catch (\Exception $e) {
                Log::error('Mailrelay API request failed', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                ]);

                throw MailrelayException::connectionFailed($e->getMessage());
            }
        }

        throw MailrelayException::connectionFailed('Max retry attempts reached');
    }

    /**
     * Sync a subscriber to Mailrelay
     *
     * @param  array  $groups  Optional group IDs to assign
     * @return array Mailrelay response
     *
     * @throws MailrelayException
     */
    public function syncSubscriber(Subscriber $subscriber, array $groups = []): array
    {
        try {
            $data = [
                'email' => $subscriber->email,
                'name' => $subscriber->name,
            ];

            if ($subscriber->custom_fields) {
                $data['custom_fields'] = $subscriber->custom_fields;
            }

            if (! empty($groups)) {
                $data['groups'] = $groups;
            }

            if ($subscriber->mailrelay_id) {
                $response = $this->request('PUT', "/subscribers/{$subscriber->mailrelay_id}", $data);
            } else {
                $response = $this->request('POST', '/subscribers', $data);

                if (isset($response['id'])) {
                    $subscriber->update([
                        'mailrelay_id' => $response['id'],
                        'last_synced_at' => now(),
                    ]);
                }
            }

            $subscriber->update(['last_synced_at' => now()]);

            return $response;

        } catch (\Exception $e) {
            throw MailrelayException::syncFailed($subscriber->email, $e->getMessage());
        }
    }

    /**
     * Get all groups from Mailrelay
     *
     * @param  array  $filters  Optional filters
     * @return array Groups data
     */
    public function getGroups(array $filters = []): array
    {
        $cacheKey = config('mailrelay.cache.prefix').':groups:'.md5(json_encode($filters));
        $cacheTtl = config('mailrelay.cache.ttl.groups', 3600);

        if (config('mailrelay.cache.enabled')) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($filters) {
                return $this->request('GET', '/groups', $filters);
            });
        }

        return $this->request('GET', '/groups', $filters);
    }

    /**
     * Sync groups from Mailrelay to local database
     *
     * @return int Number of groups synced
     */
    public function syncGroups(): int
    {
        try {
            $response = $this->getGroups();
            $groups = $response['data'] ?? $response;

            if (! is_array($groups)) {
                return 0;
            }

            $syncedCount = 0;

            foreach ($groups as $groupData) {
                MailrelayGroup::updateOrCreate(
                    ['mailrelay_group_id' => $groupData['id']],
                    [
                        'name' => $groupData['name'] ?? '',
                        'description' => $groupData['description'] ?? null,
                        'subscriber_count' => $groupData['subscriber_count'] ?? 0,
                        'last_synced_at' => now(),
                    ]
                );

                $syncedCount++;
            }

            if (config('mailrelay.cache.enabled') && config('mailrelay.cache.auto_invalidate')) {
                Cache::forget(config('mailrelay.cache.prefix').':groups:'.md5(json_encode([])));
            }

            return $syncedCount;

        } catch (\Exception $e) {
            Log::error('Failed to sync Mailrelay groups', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Create a campaign in Mailrelay
     *
     * @param  array  $options  Additional campaign options
     * @return array Mailrelay response
     *
     * @throws MailrelayException
     */
    public function createCampaign(Campaign $campaign, array $options = []): array
    {
        try {
            $data = [
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'html_content' => $campaign->html_content,
                'plain_content' => $campaign->plain_content ?? strip_tags($campaign->html_content),
                'sender_id' => $campaign->sender_id ?? config('mailrelay.campaign.default_sender_id'),
            ];

            $data = array_merge($data, $options);

            $response = $this->request('POST', '/campaigns', $data);

            if (isset($response['id'])) {
                $campaign->update([
                    'mailrelay_campaign_id' => $response['id'],
                ]);
            }

            return $response;

        } catch (\Exception $e) {
            throw MailrelayException::campaignFailed($e->getMessage());
        }
    }

    /**
     * Send a campaign via Mailrelay
     *
     * @param  array  $options  Send options (groups, test_email, etc.)
     * @return array Mailrelay response
     *
     * @throws MailrelayException
     */
    public function sendCampaign(Campaign $campaign, array $options = []): array
    {
        if (! $campaign->mailrelay_campaign_id) {
            throw MailrelayException::campaignFailed('Campaign must be created in Mailrelay first');
        }

        try {
            $campaign->markAsSending();

            $response = $this->request(
                'POST',
                "/campaigns/{$campaign->mailrelay_campaign_id}/send",
                $options
            );

            $campaign->markAsSent();

            if (config('mailrelay.campaign.track_analytics')) {
                CampaignAnalytics::updateOrCreate(
                    ['campaign_id' => $campaign->id],
                    ['last_synced_at' => now()]
                );
            }

            return $response;

        } catch (\Exception $e) {
            $campaign->markAsFailed();
            throw MailrelayException::campaignFailed($e->getMessage());
        }
    }

    /**
     * Get campaign analytics from Mailrelay
     *
     * @return array Analytics data
     */
    public function getCampaignAnalytics(Campaign $campaign): array
    {
        if (! $campaign->mailrelay_campaign_id) {
            return [];
        }

        $cacheKey = config('mailrelay.cache.prefix').":analytics:{$campaign->mailrelay_campaign_id}";
        $cacheTtl = config('mailrelay.cache.ttl.analytics', 300);

        if (config('mailrelay.cache.enabled')) {
            return Cache::remember($cacheKey, $cacheTtl, function () use ($campaign) {
                return $this->request('GET', "/campaigns/{$campaign->mailrelay_campaign_id}/analytics");
            });
        }

        return $this->request('GET', "/campaigns/{$campaign->mailrelay_campaign_id}/analytics");
    }

    /**
     * Sync campaign analytics from Mailrelay to local database
     *
     * @return bool Success status
     */
    public function syncCampaignAnalytics(Campaign $campaign): bool
    {
        try {
            $analyticsData = $this->getCampaignAnalytics($campaign);

            if (empty($analyticsData)) {
                return false;
            }

            $analytics = CampaignAnalytics::updateOrCreate(
                ['campaign_id' => $campaign->id],
                [
                    'sent_count' => $analyticsData['sent'] ?? 0,
                    'opened_count' => $analyticsData['opened'] ?? 0,
                    'clicked_count' => $analyticsData['clicked'] ?? 0,
                    'bounced_count' => $analyticsData['bounced'] ?? 0,
                    'unsubscribed_count' => $analyticsData['unsubscribed'] ?? 0,
                    'last_synced_at' => now(),
                ]
            );

            $analytics->updateRates();

            if (config('mailrelay.cache.enabled') && config('mailrelay.cache.auto_invalidate')) {
                $cacheKey = config('mailrelay.cache.prefix').":analytics:{$campaign->mailrelay_campaign_id}";
                Cache::forget($cacheKey);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync campaign analytics', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Unsubscribe a subscriber from Mailrelay
     *
     * @return array Mailrelay response
     *
     * @throws MailrelayException
     */
    public function unsubscribe(Subscriber $subscriber): array
    {
        if (! $subscriber->mailrelay_id) {
            throw MailrelayException::subscriberNotFound(0);
        }

        try {
            $response = $this->request(
                'POST',
                "/subscribers/{$subscriber->mailrelay_id}/unsubscribe"
            );

            $subscriber->unsubscribe();

            return $response;

        } catch (\Exception $e) {
            throw MailrelayException::syncFailed($subscriber->email, $e->getMessage());
        }
    }

    /**
     * Test connection to Mailrelay API
     *
     * @return array Connection test result
     */
    public function testConnection(): array
    {
        try {
            $response = $this->request('GET', '/account');

            return [
                'success' => true,
                'message' => 'Successfully connected to Mailrelay API',
                'account' => $response,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to Mailrelay API',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get package information from Mailrelay
     *
     * @return array Package info
     */
    public function getPackageInfo(): array
    {
        try {
            return $this->request('GET', '/account/package');
        } catch (\Exception $e) {
            Log::error('Failed to get Mailrelay package info', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Create a webhook in Mailrelay
     *
     * @param  string  $url  Webhook URL
     * @param  array  $events  Events to subscribe to
     * @return array Mailrelay response
     *
     * @throws MailrelayException
     */
    public function createWebhook(string $url, array $events = []): array
    {
        if (empty($events)) {
            $events = config('mailrelay.webhook.events', [
                'email.opened',
                'email.clicked',
                'subscriber.unsubscribed',
                'email.bounced',
                'email.complained',
            ]);
        }

        try {
            $data = [
                'url' => $url,
                'events' => $events,
                'active' => true,
            ];

            if ($secret = config('mailrelay.webhook.secret')) {
                $data['secret'] = $secret;
            }

            return $this->request('POST', '/webhooks', $data);

        } catch (\Exception $e) {
            throw MailrelayException::connectionFailed('Failed to create webhook: '.$e->getMessage());
        }
    }

    /**
     * Get subscriber by email from Mailrelay
     *
     * @return array|null Subscriber data
     */
    public function getSubscriber(string $email): ?array
    {
        try {
            $response = $this->request('GET', '/subscribers', ['email' => $email]);

            return $response['data'][0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Delete subscriber from Mailrelay
     *
     * @return bool Success status
     */
    public function deleteSubscriber(Subscriber $subscriber): bool
    {
        if (! $subscriber->mailrelay_id) {
            return false;
        }

        try {
            $this->request('DELETE', "/subscribers/{$subscriber->mailrelay_id}");
            $subscriber->update(['mailrelay_id' => null, 'last_synced_at' => now()]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete subscriber from Mailrelay', [
                'subscriber_id' => $subscriber->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Add subscriber to a group in Mailrelay
     *
     * @param  int  $groupId  Mailrelay group ID
     * @return bool Success status
     */
    public function addSubscriberToGroup(Subscriber $subscriber, int $groupId): bool
    {
        if (! $subscriber->mailrelay_id) {
            return false;
        }

        try {
            $this->request('POST', "/subscribers/{$subscriber->mailrelay_id}/groups", [
                'group_id' => $groupId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add subscriber to group', [
                'subscriber_id' => $subscriber->id,
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Remove subscriber from a group in Mailrelay
     *
     * @param  int  $groupId  Mailrelay group ID
     * @return bool Success status
     */
    public function removeSubscriberFromGroup(Subscriber $subscriber, int $groupId): bool
    {
        if (! $subscriber->mailrelay_id) {
            return false;
        }

        try {
            $this->request('DELETE', "/subscribers/{$subscriber->mailrelay_id}/groups/{$groupId}");

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove subscriber from group', [
                'subscriber_id' => $subscriber->id,
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
