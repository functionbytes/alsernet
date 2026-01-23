<?php

namespace Modules\Health\Checks;

use Illuminate\Support\Facades\Storage;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class StorageCheck extends Check
{
    public function run(): Result
    {
        $result = Result::make();

        try {
            $testFile = 'health_check_'.time().'.txt';
            $testContent = 'Health check test at '.now();

            // Test write
            Storage::put($testFile, $testContent);

            // Test read
            $content = Storage::get($testFile);

            // Test delete
            Storage::delete($testFile);

            $result->ok()
                ->shortSummary('Storage is writable')
                ->meta([
                    'disk' => config('filesystems.default'),
                    'test_successful' => $content === $testContent,
                ]);
        } catch (\Exception $e) {
            $result->failed()
                ->shortSummary('Storage is not writable')
                ->notificationMessage('Storage check failed: '.$e->getMessage());
        }

        return $result;
    }
}
