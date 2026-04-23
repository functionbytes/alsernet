<?php

namespace Modules\Notification\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Notification\Jobs\SendNotificationDigestJob;

class SendNotificationDigestCommand extends Command
{
    protected $signature = 'notifications:send-digest';

    protected $description = 'Enviar resumen diario de notificaciones no leídas a cada usuario';

    public function handle(): int
    {
        $dispatched = 0;

        User::query()
            ->whereHas('notifications', function ($query) {
                $query->whereNull('read_at')
                    ->where('created_at', '>=', now()->subDay());
            })
            ->chunkById(500, function ($users) use (&$dispatched) {
                foreach ($users as $user) {
                    SendNotificationDigestJob::dispatch($user);
                    $dispatched++;
                }
            });

        $this->info("Digest jobs dispatched for {$dispatched} user(s).");

        return self::SUCCESS;
    }
}
