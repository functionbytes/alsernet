<?php

namespace Modules\Health\Checks;

use Illuminate\Support\Facades\DB;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class DatabaseCheck extends Check
{
    public function run(): Result
    {
        $result = Result::make();

        try {
            $startTime = microtime(true);

            // Test database connection
            DB::connection()->getPdo();

            // Simple query test
            $users = DB::table('users')->count();

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $result->ok()
                ->shortSummary('Database connection OK')
                ->meta([
                    'connection' => config('database.default'),
                    'response_time_ms' => $responseTime,
                    'users_count' => $users,
                ]);
        } catch (\Exception $e) {
            $result->failed()
                ->shortSummary('Database connection failed')
                ->notificationMessage('Database is not accessible: '.$e->getMessage());
        }

        return $result;
    }
}
