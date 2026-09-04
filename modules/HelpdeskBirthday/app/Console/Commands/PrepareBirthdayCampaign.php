<?php

namespace Modules\HelpdeskBirthday\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Services\BirthdayAudienceService;
use Modules\HelpdeskBirthday\Services\BirthdayCampaignService;
use Modules\HelpdeskBirthday\Support\BirthdaySettings;

/**
 * Crea la campaña del día: resuelve el cupón, trae los cumpleañeros del ERP y
 * reparte las horas de envío. No envía nada — de eso se encarga dispatch-due.
 */
class PrepareBirthdayCampaign extends Command
{
    protected $signature = 'helpdeskbirthday:prepare
                            {--date= : Día a preparar (Y-m-d). Por defecto, hoy}
                            {--dry-run : Solo muestra a quién se enviaría, sin crear la campaña}';

    protected $description = 'Prepara la campaña de cumpleaños del día con su cupón y sus destinatarios';

    public function handle(
        BirthdayCampaignService $campaigns,
        BirthdayAudienceService $audience,
        BirthdaySettings $settings,
    ): int {
        if (! helpdesk_birthday_enabled()) {
            $this->warn('El módulo de cumpleaños está desactivado.');

            return self::SUCCESS;
        }

        $date = $this->resolveDate();

        if ($date === null) {
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            return $this->dryRun($audience, $settings, $date);
        }

        $campaign = $campaigns->prepare($date);

        if ($campaign->status === BirthdayCampaign::STATUS_FAILED) {
            $this->error($campaign->error_message ?? 'La campaña no se pudo preparar.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Campaña %s: %d destinatarios (%d omitidos), 1 correo cada %d s desde las %s.',
            $date->toDateString(),
            $campaign->recipients_total,
            $campaign->skipped_count,
            (int) $campaign->interval_seconds,
            substr((string) $campaign->window_start, 0, 5),
        ));

        return self::SUCCESS;
    }

    private function dryRun(BirthdayAudienceService $audience, BirthdaySettings $settings, CarbonImmutable $date): int
    {
        try {
            $recipients = $audience->fetchForDate($date, $settings->exclusions());
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('%d cumpleañeros el %s:', count($recipients), $date->toDateString()));

        $this->table(
            ['ERP', 'Email', 'Nombre', 'Nacimiento', 'Estado'],
            array_map(static fn (array $r): array => [
                $r['erp_customer_id'],
                $r['email'],
                $r['name'],
                $r['birth_date'],
                $r['skip_reason'] ?? $r['status'],
            ], array_slice($recipients, 0, 50)),
        );

        if (count($recipients) > 50) {
            $this->line(sprintf('… y %d más.', count($recipients) - 50));
        }

        return self::SUCCESS;
    }

    private function resolveDate(): ?CarbonImmutable
    {
        $option = $this->option('date');

        if (! $option) {
            return CarbonImmutable::today();
        }

        try {
            return CarbonImmutable::parse($option)->startOfDay();
        } catch (\Throwable) {
            $this->error("Fecha inválida: {$option}. Se espera Y-m-d.");

            return null;
        }
    }
}
