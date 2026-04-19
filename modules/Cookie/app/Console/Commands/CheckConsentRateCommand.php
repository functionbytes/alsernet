<?php

namespace Modules\Cookie\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Cookie\Models\CookieConsentLog;
use Modules\Cookie\Notifications\LowConsentRateNotification;
use Modules\Core\Models\Setting;

class CheckConsentRateCommand extends Command
{
    protected $signature = 'cookie:check-consent-rate';

    protected $description = 'Verifica tasa de aceptación y notifica si está por debajo del umbral';

    public function handle(): int
    {
        $threshold = (int) Setting::get('cookie.alert_threshold', 30);

        $total = CookieConsentLog::query()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($total < 10) {
            $this->info('Datos insuficientes para calcular la tasa (menos de 10 registros en 24h).');

            return self::SUCCESS;
        }

        $acceptCount = CookieConsentLog::query()
            ->where('created_at', '>=', now()->subDay())
            ->where('action', 'accept_all')
            ->count();

        $rate = round(($acceptCount / $total) * 100, 1);

        $this->info("Tasa de aceptación: {$rate}% (umbral: {$threshold}%)");

        if ($rate < $threshold) {
            $admin = User::query()
                ->whereHas('permissions', fn ($q) => $q->where('name', 'cookie.settings.view'))
                ->orWhereHas('roles.permissions', fn ($q) => $q->where('name', 'cookie.settings.view'))
                ->first();

            $admin?->notify(new LowConsentRateNotification($rate, $threshold));
        }

        return self::SUCCESS;
    }
}
