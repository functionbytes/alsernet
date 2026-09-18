<?php

namespace Modules\HelpdeskSocial\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Notifications\SocialAccountHealthAlertNotification;

class SocialHealthCheckCommand extends Command
{
    protected $signature = 'helpdesk-social:health-check
                            {--notify : Enviar notificación a administradores}';

    protected $description = 'Verifica el estado de las cuentas sociales y reporta problemas';

    public function handle(): int
    {
        $issues = [];

        // 1. Tokens próximos a expirar
        $expiringAccounts = SocialAccount::active()
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', now()->addDays(7))
            ->get();

        if ($expiringAccounts->isNotEmpty()) {
            foreach ($expiringAccounts as $account) {
                $days = (int) $account->token_expires_at->diffInDays(now());
                $issues[] = "[TOKEN] {$account->name} ({$account->platform}) expira en {$days} días";
                $this->warn("Token de {$account->name} expira en {$days} días");
            }
        }

        // 2. Cuentas desactivadas por fallos consecutivos
        $disabledAccounts = SocialAccount::where('is_active', false)
            ->where('consecutive_failures', '>=', 5)
            ->get();

        if ($disabledAccounts->isNotEmpty()) {
            foreach ($disabledAccounts as $account) {
                $issues[] = "[CIRCUIT] {$account->name} ({$account->platform}) desactivada tras {$account->consecutive_failures} fallos";
                $this->error("{$account->name} desactivada por circuit breaker.");
            }
        }

        // 3. Cuentas sin sincronización reciente
        $staleAccounts = SocialAccount::active()->stale()->get();

        if ($staleAccounts->isNotEmpty()) {
            foreach ($staleAccounts as $account) {
                $lastSync = $account->last_synced_at?->diffForHumans() ?? 'nunca';
                $issues[] = "[STALE] {$account->name} última sync: {$lastSync}";
                $this->warn("{$account->name} sin sincronización reciente (última: {$lastSync})");
            }
        }

        // 4. Cuentas con error reciente
        $recentErrors = SocialAccount::active()
            ->whereNotNull('last_error_at')
            ->where('last_error_at', '>=', now()->subHour())
            ->get();

        if ($recentErrors->isNotEmpty()) {
            foreach ($recentErrors as $account) {
                $issues[] = "[ERROR] {$account->name}: ".mb_substr($account->last_error_message ?? '', 0, 100);
                $this->error("{$account->name} tuvo un error reciente.");
            }
        }

        if (empty($issues)) {
            $this->info('✅ Todas las cuentas sociales están saludables.');

            return self::SUCCESS;
        }

        if ($this->option('notify')) {
            $this->notifyAdmins($issues);
        }

        $this->info('Health check completado. Problemas encontrados: '.count($issues));

        return self::SUCCESS;
    }

    private function notifyAdmins(array $issues): void
    {
        try {
            $admins = User::role(['manager', 'super-admin'])->get();
        } catch (\Throwable $e) {
            $this->warn('No se pudieron obtener administradores: '.$e->getMessage());

            return;
        }

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new SocialAccountHealthAlertNotification($issues));
        $this->info('Notificación enviada a '.$admins->count().' administrador(es).');
    }
}
