<?php

namespace Modules\Horizon\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Collection;

class HorizonStatsService
{
    private const HORIZON_PREFIX = 'laravel_horizon:';

    /**
     * Get overall queue statistics
     */
    public function getOverallStats(): array
    {
        return [
            'total_jobs' => $this->getTotalJobs(),
            'pending_jobs' => $this->getPendingJobs(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'processed_count' => $this->getProcessedCount(),
            'jobs_per_minute' => $this->getJobsPerMinute(),
            'average_wait_time' => $this->getAverageWaitTime(),
            'average_throughput' => $this->getAverageThroughput(),
            'uptime' => $this->getHorizonUptime(),
        ];
    }

    /**
     * Get recent jobs data
     */
    public function getRecentJobs(int $limit = 50): array
    {
        try {
            $redis = Redis::connection();
            $jobs = [];

            // Get recent completed jobs from Horizon
            $recentJobs = $redis->zrevrange(
                self::HORIZON_PREFIX . 'recent_jobs',
                0,
                $limit - 1,
                ['WITHSCORES' => true]
            );

            foreach ($recentJobs as $jobId => $timestamp) {
                $jobData = $redis->hgetall(self::HORIZON_PREFIX . "job:{$jobId}");
                if (! empty($jobData)) {
                    $jobs[] = array_merge(
                        $jobData,
                        ['timestamp' => $timestamp, 'id' => $jobId]
                    );
                }
            }

            return $jobs;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get failed jobs
     */
    public function getFailedJobs(int $limit = 50): array
    {
        try {
            $redis = Redis::connection();
            $jobs = [];

            $failedKeys = $redis->zrevrange(
                self::HORIZON_PREFIX . 'failed_jobs',
                0,
                $limit - 1,
                ['WITHSCORES' => true]
            );

            foreach ($failedKeys as $jobId => $timestamp) {
                $jobData = $redis->hgetall(self::HORIZON_PREFIX . "failed_job:{$jobId}");
                if (! empty($jobData)) {
                    $jobs[] = array_merge(
                        $jobData,
                        ['timestamp' => $timestamp, 'id' => $jobId]
                    );
                }
            }

            return $jobs;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get total number of jobs
     */
    public function getTotalJobs(): int
    {
        try {
            return (int) Redis::connection()->get(self::HORIZON_PREFIX . 'stats:total_jobs') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of pending jobs
     */
    public function getPendingJobs(): int
    {
        try {
            $redis = Redis::connection();
            $count = 0;

            // Count jobs in all queues
            $queues = ['default', 'emails', 'deliveries', 'pdf-generation', 'bulk-operations', 'notifications', 'erp', 'high', 'ai-content'];

            foreach ($queues as $queue) {
                $count += (int) $redis->llen('queues:' . $queue);
            }

            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of failed jobs
     */
    public function getFailedJobsCount(): int
    {
        try {
            return (int) Redis::connection()->zcard(self::HORIZON_PREFIX . 'failed_jobs') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get processed jobs count
     */
    public function getProcessedCount(): int
    {
        try {
            return (int) Redis::connection()->get(self::HORIZON_PREFIX . 'stats:processed') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get jobs per minute metric
     */
    public function getJobsPerMinute(): float
    {
        try {
            return (float) Redis::connection()->get(self::HORIZON_PREFIX . 'stats:jobs_per_minute') ?? 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get average wait time in seconds
     */
    public function getAverageWaitTime(): int
    {
        try {
            return (int) Redis::connection()->get(self::HORIZON_PREFIX . 'stats:avg_wait_time') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get average throughput
     */
    public function getAverageThroughput(): float
    {
        try {
            return (float) Redis::connection()->get(self::HORIZON_PREFIX . 'stats:throughput') ?? 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get Horizon uptime
     */
    public function getHorizonUptime(): int
    {
        try {
            return (int) Redis::connection()->get(self::HORIZON_PREFIX . 'stats:uptime') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get metrics data for charts
     */
    public function getMetrics(): array
    {
        return [
            'total_processed' => $this->getProcessedCount(),
            'total_failed' => $this->getFailedJobsCount(),
            'throughput' => $this->getAverageThroughput(),
            'wait_time' => $this->getAverageWaitTime(),
        ];
    }

    /**
     * Get throughput data for chart visualization
     */
    public function getThroughputData(): array
    {
        try {
            $redis = Redis::connection();
            $data = [];

            // Get last 24 hours of throughput data
            for ($i = 0; $i < 24; $i++) {
                $hour = now()->subHours($i)->format('Y-m-d H:00');
                $key = self::HORIZON_PREFIX . "throughput:{$hour}";
                $value = (int) $redis->get($key) ?? 0;

                $data[$hour] = $value;
            }

            return array_reverse($data);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retry a specific failed job
     */
    public function retryJob(string $jobId): bool
    {
        try {
            $redis = Redis::connection();

            $jobData = $redis->hgetall(self::HORIZON_PREFIX . "failed_job:{$jobId}");

            if (empty($jobData)) {
                return false;
            }

            // Move from failed to pending queue
            $queue = $jobData['queue'] ?? 'default';
            $redis->rpush('queues:' . $queue, $jobId);

            // Remove from failed jobs
            $redis->zrem(self::HORIZON_PREFIX . 'failed_jobs', $jobId);
            $redis->del(self::HORIZON_PREFIX . "failed_job:{$jobId}");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retry all failed jobs
     */
    public function retryAllFailed(): int
    {
        try {
            $redis = Redis::connection();
            $count = 0;

            $failedJobs = $redis->zrange(self::HORIZON_PREFIX . 'failed_jobs', 0, -1);

            foreach ($failedJobs as $jobId) {
                if ($this->retryJob($jobId)) {
                    $count++;
                }
            }

            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Delete/forget a failed job
     */
    public function forgetJob(string $jobId): bool
    {
        try {
            $redis = Redis::connection();

            // Remove from failed jobs
            $redis->zrem(self::HORIZON_PREFIX . 'failed_jobs', $jobId);
            $redis->del(self::HORIZON_PREFIX . "failed_job:{$jobId}");

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
