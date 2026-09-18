<?php

namespace Modules\Notification\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Helpdesk\Models\Setting;
use Modules\Notification\Jobs\SendNotificationDigestJob;

class SendNotificationDigestCommand extends Command
{
    protected $signature = 'notifications:send-digest';

    protected $description = 'Enviar resumen diario de notificaciones no leídas a cada usuario';

    public function handle(): int
    {
        // Checkbox real: /panel/settings/helpdesk/notifications ("Activar
        // resumen diario por email"). Antes se guardaba pero nadie lo leía,
        // así que apagarlo no tenía ningún efecto.
        if (! Setting::get('notifications.daily_digest_enabled', false)) {
            $this->info('Resumen diario deshabilitado en Ajustes > Notificaciones. No se envía nada.');

            return self::SUCCESS;
        }

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
