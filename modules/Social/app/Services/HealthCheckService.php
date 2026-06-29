<?php

namespace Modules\Social\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Modules\Social\Models\Post;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Services\Publishers\FacebookPublisher;
use Modules\Social\Services\Publishers\InstagramPublisher;
use Modules\Social\Services\Publishers\LinkedInPublisher;
use Modules\Social\Services\Publishers\TwitterPublisher;

class HealthCheckService
{
    /**
     * Run all health checks
     */
    public function runAllChecks(): array
    {
        return [
            'overall_status' => 'checking',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'oauth_connections' => $this->checkOAuthConnections(),
                'publishers' => $this->checkPublishers(),
                'webhooks' => $this->checkWebhooks(),
                'queue' => $this->checkQueue(),
                'scheduler' => $this->checkScheduler(),
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
            ],
        ];
    }

    /**
     * Check OAuth connections and token validity
     */
    protected function checkOAuthConnections(): array
    {
        $accounts = SocialAccount::where('status', 1)->get();
        $results = [];
        $healthyCount = 0;

        foreach ($accounts as $account) {
            try {
                // Test API call with token
                $valid = $this->validateToken($account);

                $status = 'healthy';
                if (! $valid) {
                    $status = 'invalid_token';
                } elseif ($account->access_token_expires_at && $account->access_token_expires_at->isPast()) {
                    $status = 'expired';
                } elseif ($account->access_token_expires_at && $account->access_token_expires_at->diffInDays(now()) < 7) {
                    $status = 'expiring_soon';
                } else {
                    $healthyCount++;
                }

                $results[] = [
                    'id' => $account->id,
                    'account' => $account->username,
                    'network' => $account->network->label(),
                    'status' => $status,
                    'expires_at' => $account->access_token_expires_at?->toDateTimeString(),
                    'days_until_expiry' => $account->access_token_expires_at?->diffInDays(now()),
                ];
            } catch (Exception $e) {
                $results[] = [
                    'id' => $account->id,
                    'account' => $account->username,
                    'network' => $account->network->label(),
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'status' => $healthyCount === $accounts->count() ? 'healthy' : 'degraded',
            'total_accounts' => $accounts->count(),
            'healthy_accounts' => $healthyCount,
            'accounts' => $results,
        ];
    }

    /**
     * Validate token with a simple API call
     */
    protected function validateToken(SocialAccount $account): bool
    {
        try {
            $token = decrypt($account->access_token);

            return match ($account->network->value) {
                'facebook' => $this->validateFacebookToken($token, $account->network_id),
                'instagram' => $this->validateInstagramToken($token, $account->network_id),
                'twitter' => $this->validateTwitterToken($token),
                'linkedin' => $this->validateLinkedInToken($token),
                default => false,
            };
        } catch (Exception $e) {
            return false;
        }
    }

    protected function validateFacebookToken(string $token, string $pageId): bool
    {
        $response = Http::get("https://graph.facebook.com/v21.0/{$pageId}", [
            'fields' => 'id,name',
            'access_token' => $token,
        ]);

        return $response->successful();
    }

    protected function validateInstagramToken(string $token, string $accountId): bool
    {
        $response = Http::get("https://graph.facebook.com/v21.0/{$accountId}", [
            'fields' => 'id,username',
            'access_token' => $token,
        ]);

        return $response->successful();
    }

    protected function validateTwitterToken(string $token): bool
    {
        $response = Http::withToken($token)
            ->get('https://api.twitter.com/2/users/me');

        return $response->successful();
    }

    protected function validateLinkedInToken(string $token): bool
    {
        $response = Http::withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->get('https://api.linkedin.com/v2/me');

        return $response->successful();
    }

    /**
     * Check publishers health
     */
    protected function checkPublishers(): array
    {
        $publishers = [
            'facebook' => FacebookPublisher::class,
            'instagram' => InstagramPublisher::class,
            'twitter' => TwitterPublisher::class,
            'linkedin' => LinkedInPublisher::class,
        ];

        $results = [];
        $healthyCount = 0;

        foreach ($publishers as $network => $publisherClass) {
            try {
                // Check if class exists and is instantiable
                if (class_exists($publisherClass)) {
                    $healthyCount++;
                    $results[$network] = [
                        'status' => 'healthy',
                        'class' => $publisherClass,
                    ];
                } else {
                    $results[$network] = [
                        'status' => 'missing',
                        'class' => $publisherClass,
                    ];
                }
            } catch (Exception $e) {
                $results[$network] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'status' => $healthyCount === count($publishers) ? 'healthy' : 'degraded',
            'total' => count($publishers),
            'healthy' => $healthyCount,
            'publishers' => $results,
        ];
    }

    /**
     * Check webhooks accessibility
     */
    protected function checkWebhooks(): array
    {
        $webhooks = [
            'facebook' => url('/webhooks/social/facebook'),
            'instagram' => url('/webhooks/social/instagram'),
            'twitter' => url('/webhooks/social/twitter'),
            'linkedin' => url('/webhooks/social/linkedin'),
        ];

        $results = [];
        $healthyCount = 0;

        foreach ($webhooks as $network => $url) {
            try {
                // Test GET (should return 200 or 405)
                $response = Http::timeout(5)->get($url);

                $status = $response->successful() || $response->status() === 405 ? 'reachable' : 'unreachable';
                if ($status === 'reachable') {
                    $healthyCount++;
                }

                $results[$network] = [
                    'url' => $url,
                    'status' => $status,
                    'response_code' => $response->status(),
                ];
            } catch (Exception $e) {
                $results[$network] = [
                    'url' => $url,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'status' => $healthyCount === count($webhooks) ? 'healthy' : 'degraded',
            'total' => count($webhooks),
            'reachable' => $healthyCount,
            'webhooks' => $results,
        ];
    }

    /**
     * Check queue workers status
     */
    protected function checkQueue(): array
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();
            $lastJob = DB::table('jobs')->latest('id')->first();

            $status = 'healthy';
            if ($failedJobs > 10) {
                $status = 'degraded';
            } elseif ($failedJobs > 50) {
                $status = 'unhealthy';
            }

            if ($pendingJobs > 100) {
                $status = 'backlogged';
            }

            return [
                'status' => $status,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'last_job_created_at' => $lastJob?->created_at,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check scheduler status
     */
    protected function checkScheduler(): array
    {
        try {
            // Get recent posts that should have been published
            $recentScheduled = Post::where('status', 'scheduled')
                ->where('scheduled_at', '<=', now()->subMinutes(5))
                ->count();

            $status = 'healthy';
            $message = 'Scheduler appears to be running normally';

            if ($recentScheduled > 0) {
                $status = 'stale';
                $message = "{$recentScheduled} posts are overdue for publishing. Scheduler may not be running.";
            }

            return [
                'status' => $status,
                'message' => $message,
                'overdue_posts' => $recentScheduled,
                'next_run' => 'Every minute',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check database connectivity
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $postsCount = Post::count();

            return [
                'status' => 'healthy',
                'connection' => 'active',
                'total_posts' => $postsCount,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    protected function checkRedis(): array
    {
        try {
            Redis::ping();

            return [
                'status' => 'healthy',
                'connection' => 'active',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get overall system health
     */
    public function getOverallHealth(): string
    {
        $checks = $this->runAllChecks();

        $statuses = collect($checks['checks'])->pluck('status');

        if ($statuses->contains('error') || $statuses->contains('unhealthy')) {
            return 'unhealthy';
        }

        if ($statuses->contains('degraded') || $statuses->contains('stale')) {
            return 'degraded';
        }

        return 'healthy';
    }
}
