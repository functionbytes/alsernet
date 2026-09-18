<?php

namespace Modules\Auth\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delete a user account (soft delete) and revoke all active credentials.
 * Dispatched after self-service delete request to decouple from the HTTP
 * response and allow a grace window via the queue's delayed dispatch.
 */
class DeleteUserAccountJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct(
        private readonly int $userId,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $user->tokens()->delete();
        $user->sessions()->delete();
        $user->forceFill([
            'available' => false,
            'email' => "deleted_{$user->id}_{$user->email}",
        ])->save();
        $user->delete();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Account deletion job failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
