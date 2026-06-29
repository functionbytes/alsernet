<?php

namespace Modules\Auth\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Auth\Events\PasswordChanged;
use Modules\Auth\Services\PasswordHistoryService;

class RecordPasswordChange implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public function __construct(
        private readonly PasswordHistoryService $history,
    ) {}

    public function handle(PasswordChanged $event): void
    {
        if ($event->user->password) {
            $this->history->record($event->user, $event->user->password);
        }
    }
}
