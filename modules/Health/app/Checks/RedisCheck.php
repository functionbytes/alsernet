<?php

namespace Modules\Health\Checks;

use Illuminate\Support\Facades\Redis;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class RedisCheck extends Check
{
    public function run(): Result
    {
        $result = Result::make();

        if (config('cache.default') !== 'redis' && config('session.driver') !== 'redis') {
            return $result->ok()->shortSummary('Redis not configured');
        }

        try {
            $startTime = microtime(true);

            // Test Redis connection
            Redis::ping();

            // Test set/get
            $testKey = 'health_check_'.time();
            Redis::setex($testKey, 60, 'test');
            $value = Redis::get($testKey);
            Redis::del($testKey);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $result->ok()
                ->shortSummary('Redis connection OK')
                ->meta([
                    'response_time_ms' => $responseTime,
                    'test_successful' => $value === 'test',
                ]);
        } catch (\Exception $e) {
            $result->failed()
                ->shortSummary('Redis connection failed')
                ->notificationMessage('Redis is not accessible: '.$e->getMessage());
        }

        return $result;
    }
}
