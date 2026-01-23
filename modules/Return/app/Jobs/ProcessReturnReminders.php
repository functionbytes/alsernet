<?php

namespace Modules\Returns\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Returns\Models\ReturnRequest as ReturnModel;
use Modules\Returns\Services\ReturnNotificationService;

class ProcessReturnReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    private ReturnModel $return;

    /**
     * Create a new job instance.
     */
    public function __construct(ReturnModel $return)
    {
        $this->return = $return;
    }

    /**
     * Execute the job.
     */
    public function handle(ReturnNotificationService $notificationService): void
    {
        try {
            Log::info('Processing return reminder job', [
                'return_id' => $this->return->id,
                'return_number' => $this->return->number,
            ]);

            $notificationService->sendReminder($this->return);

            Log::info('Return reminder job completed successfully', [
                'return_id' => $this->return->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Return reminder job failed', [
                'return_id' => $this->return->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Return reminder job failed after all retries', [
            'return_id' => $this->return->id,
            'error' => $exception->getMessage(),
        ]);

        // Notificar al administrador
        // Mail::to(config('returns.notifications.admin_email'))
        //     ->send(new JobFailedNotification($this->return, $exception));
    }
}
