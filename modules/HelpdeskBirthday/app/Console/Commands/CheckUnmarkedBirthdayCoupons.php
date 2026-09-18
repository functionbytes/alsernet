<?php

namespace Modules\HelpdeskBirthday\Console\Commands;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Notifications\UnmarkedCouponsNotification;
use Modules\HelpdeskBirthday\Services\BirthdayRedemptionService;
use Throwable;

/**
 * Busca cupones que se aplicaron en la tienda pero no llegaron a descontarse en
 * gestión, y avisa.
 *
 * El descuento ya se le hizo al cliente y el bono sigue vivo en el ERP: se
 * puede volver a gastar. Es el fallo silencioso más caro de este flujo, y hasta
 * ahora solo se veía mirando `marcarbono` a mano.
 */
class CheckUnmarkedBirthdayCoupons extends Command
{
    protected $signature = 'helpdeskbirthday:check-unmarked
                            {--days=7 : Cuántos días atrás revisar}
                            {--quiet-notify : Solo listar, sin avisar a nadie}';

    protected $description = 'Detecta cupones de cumpleaños usados en la tienda que no se marcaron en gestión';

    public function handle(BirthdayRedemptionService $redemptions): int
    {
        if (! helpdesk_birthday_enabled()) {
            return self::SUCCESS;
        }

        if (! $redemptions->isAvailable()) {
            $this->warn('Sin base de datos de PrestaShop configurada: no se puede comprobar.');

            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $since = CarbonImmutable::today()->subDays($days);

        $campaigns = BirthdayCampaign::query()
            ->whereDate('campaign_date', '>=', $since->toDateString())
            ->whereNotNull('coupon_code')
            ->get();

        $problems = [];

        foreach ($campaigns as $campaign) {
            foreach ($redemptions->detailFor($campaign) as $row) {
                // Solo lo que se usó de verdad y NO quedó marcado en gestión.
                // 'Sin registro' cuenta: significa que el canje ni siquiera
                // intentó comunicarse con el ERP.
                if ($row['erp_ok']) {
                    continue;
                }

                $problems[] = $row + ['campaign_id' => $campaign->id, 'coupon' => $campaign->coupon_code];
            }
        }

        if ($problems === []) {
            $this->info("Sin cupones pendientes de marcar en los últimos {$days} días.");

            return self::SUCCESS;
        }

        $this->warn(count($problems).' cupones usados sin descontar en gestión:');
        $this->table(
            ['Campaña', 'Cupón', 'Pedido', 'Cliente', 'Descuento', 'Gestión'],
            array_map(static fn (array $p): array => [
                $p['campaign_id'],
                $p['coupon'],
                $p['order_reference'] ?: '#'.$p['order_id'],
                $p['email'],
                number_format($p['discount'], 2, ',', '.').' €',
                $p['erp_response'] ?? 'sin registro',
            ], array_slice($problems, 0, 25)),
        );

        if (! $this->option('quiet-notify')) {
            $this->notifyManagers($problems);
        }

        // Código de salida 1: es una incidencia, y así un cron puede alertar.
        return self::FAILURE;
    }

    /**
     * @param  array<int, array<string, mixed>>  $problems
     */
    private function notifyManagers(array $problems): void
    {
        try {
            $recipients = User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin', 'super-administrador', 'super-settings']))
                ->get()
                ->filter(fn (User $user): bool => $user->can('helpdeskbirthday.manage'));

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new UnmarkedCouponsNotification(
                orders: $problems,
                campaignId: $problems[0]['campaign_id'] ?? null,
            ));
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudo avisar de los cupones sin marcar', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
