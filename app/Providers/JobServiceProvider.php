<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use App\Library\Log as MailLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class JobServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->initMailLog();

        Queue::before(function (JobProcessing $event) {
            $job = $this->getJobObject($event);
            if ($job && property_exists($job, 'monitor')) {
                $job->monitor->setRunning();
            }
        });

        // 'after' event for standalone jobs only (not batch jobs)
        Queue::after(function (JobProcessed $event) {
            $job = $this->getJobObject($event);
            if ($job && property_exists($job, 'monitor') && is_null($job->monitor->batch_id)) {
                $job->monitor->setDone();
            }
        });

        // Use failing instead of catching for compatibility with DatabaseQueue
        Queue::failing(function (JobFailed $event) {
            $job = $this->getJobObject($event);
            if ($job && property_exists($job, 'monitor') && is_null($job->monitor->batch_id)) {
                $job->monitor->setFailed($event->exception);
            }
        });
    }

    public function register(): void
    {
    }

    private function getJobObject($event): ?object
    {
        try {
            $data = $event->job->payload();
            $command = unserialize($data['data']['command']);
            return $command instanceof ShouldQueue ? $command : null;
        } catch (\Throwable $e) {
            \Log::error('Failed to unserialize job command: ' . $e->getMessage());
            return null;
        }
    }

    private function initMailLog(): void
    {
        $logPath = storage_path('logs/' . php_sapi_name() . '/mail.log');

        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0755, true);
        }

        MailLog::configure($logPath);
    }

}
