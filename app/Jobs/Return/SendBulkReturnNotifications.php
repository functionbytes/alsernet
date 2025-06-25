<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use App\Models\Return;
use App\Services\Return\ReturnNotificationService;

class SendBulkReturnNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 300;

    private Collection $returnIds;
    private string $notificationType;
    private array $customData;

    public function __construct(Collection $returnIds, string $notificationType, array $customData = [])
    {
        $this->returnIds = $returnIds;
        $this->notificationType = $notificationType;
        $this->customData = $customData;
    }

    public function handle(ReturnNotificationService $notificationService): void
    {
        $returns = Return::whereIn('id', $this->returnIds)->get();

        foreach ($returns as $return) {
            ProcessReturnNotification::dispatch($return, $this->notificationType, $this->customData)
                ->onQueue('notifications')
                ->delay(now()->addSeconds(rand(1, 10))); // Evitar sobrecarga
        }
    }
}
