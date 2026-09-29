<?php

namespace Modules\Auth\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Auth\Http\Middleware\RestrictStaffAccessByIp;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Services\StaffIpAllowlist;

/**
 * Gestión de emergencia del filtro por IP del personal (29-sep-2026).
 *
 *   php artisan auth:ip-filter status
 *   php artisan auth:ip-filter off        # break-glass: desactiva y limpia caché
 *   php artisan auth:ip-filter monitor
 *   php artisan auth:ip-filter enforce    # pide confirmación
 *
 * AUTH_STAFF_IP_FILTER_FORCE_OFF=true en .env tiene prioridad sobre esto.
 */
class IpFilterCommand extends Command
{
    protected $signature = 'auth:ip-filter
                            {action=status : status|off|monitor|enforce}
                            {--force : No pedir confirmación al activar enforce}';

    protected $description = 'Consulta o cambia el modo del filtro por IP del login y del panel';

    public function handle(StaffIpAllowlist $filter): int
    {
        $action = strtolower((string) $this->argument('action'));

        if ($action === 'status') {
            return $this->status($filter);
        }

        if (! in_array($action, StaffIpAllowlist::MODES, true)) {
            $this->error('Acción no válida. Usa: status, off, monitor o enforce.');

            return self::INVALID;
        }

        if ($action === StaffIpAllowlist::MODE_ENFORCE) {
            if ($filter->entries() === []) {
                $this->error('La lista de redes permitidas está vacía: enforce dejaría fuera a todo el personal.');

                return self::FAILURE;
            }

            if (! $this->option('force') && ! $this->confirm('Se bloquearán el login y el panel desde fuera de la lista. ¿Continuar?')) {
                return self::FAILURE;
            }
        }

        $filter->setMode($action);
        $this->info("Modo del filtro por IP: {$action} (caché limpiada).");

        if ($filter->isForcedOff()) {
            $this->warn('AUTH_STAFF_IP_FILTER_FORCE_OFF está activo: el modo efectivo sigue siendo off.');
        }

        return self::SUCCESS;
    }

    private function status(StaffIpAllowlist $filter): int
    {
        $filter->clearCache();

        $this->line('Modo efectivo: <info>'.$filter->mode().'</info> (origen: '.$filter->modeSource().')');
        $this->line('Siempre permitidas: '.implode(', ', $filter->alwaysAllowed()));

        $rows = array_map(fn ($e) => [$e['ip'], $e['description']], $filter->entries());
        $this->table(['IP / CIDR', 'Descripción'], $rows ?: [['(vacía)', '']]);

        if (StaffIpAllowlist::remoteColumnsExist()) {
            $remote = User::query()->where('remote_access_enabled', true)->get(['id', 'email', 'remote_access_until', 'two_factor_confirmed_at', 'two_factor_secret']);
            $this->table(
                ['Excepción remota', 'Hasta', '2FA', 'Efectiva'],
                $remote->map(fn (User $u) => [
                    $u->email,
                    $u->remote_access_until ?? 'sin límite',
                    $u->hasTwoFactorEnabled() ? 'sí' : 'no',
                    $filter->remoteExceptionUsable($u) ? 'sí' : 'no',
                ])->all() ?: [['(ninguna)', '', '', '']],
            );
        }

        $count = LoginAttempt::query()
            ->where('status', RestrictStaffAccessByIp::STATUS_NOT_ALLOWED)
            ->where('attempted_at', '>=', now()->subDay())
            ->count();
        $this->line("Accesos fuera de la lista (24 h, deduplicados): {$count}");

        return self::SUCCESS;
    }
}
