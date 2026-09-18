<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Models\Setting;
use Modules\HelpdeskTickets\Mail\OpsAlertMail;
use Modules\HelpdeskTickets\Services\MailReputationService;
use Throwable;

/**
 * Modal 22 "Reputación y autenticación": los checkboxes "avisar a
 * managers"/"suprimir automáticamente" (ambos OFF por defecto, ver
 * MailReputationService::settings()) no hacían nada sin algo que los
 * evaluase periódicamente contra la tasa de rebote real — este comando es
 * ese "algo". Reusa MailReputationService::report() (mismo cálculo que ve
 * el agente en el modal) para no mantener dos formas de calcular la tasa.
 *
 * Debounce igual que CheckEmailReputationCommand (módulo HelpdeskEmailActivity,
 * dominio de vigilancia distinto): solo notifica en la transición a "roto",
 * se resetea al recuperarse.
 */
class CheckTicketMailReputationCommand extends Command
{
    protected $signature = 'ticket:check-reputation';

    protected $description = 'Evalúa la tasa de rebote del ticket mailer y aplica aviso a managers / auto-supresión según lo configurado en el modal de reputación';

    public function handle(MailReputationService $service): int
    {
        $notify = filter_var(Setting::get('tickets.reputation_notify_managers', false), FILTER_VALIDATE_BOOLEAN);
        $autoSuppress = filter_var(Setting::get('tickets.reputation_auto_suppress', false), FILTER_VALIDATE_BOOLEAN);

        if (! $notify && ! $autoSuppress) {
            $this->line('Ambos interruptores están apagados, nada que evaluar.');

            return self::SUCCESS;
        }

        $rate = $service->report()['rates']['bounce_rate'] ?? null;

        if ($rate === null) {
            $this->line('Sin envíos en la ventana, nada que evaluar.');

            return self::SUCCESS;
        }

        $critical = (float) config('helpdesktickets.reputation.bounce_critical_pct', 5.0);
        $wasBreached = filter_var(Setting::get('tickets.reputation_breached', false), FILTER_VALIDATE_BOOLEAN);
        $isBreached = $rate >= $critical;

        Setting::set('tickets.reputation_breached', $isBreached);

        if ($autoSuppress) {
            Setting::set('tickets.reputation_suppressed', $isBreached);
        }

        $this->info("Tasa de rebote del ticket mailer: {$rate}% (umbral {$critical}%).");

        if ($isBreached && ! $wasBreached) {
            $this->warn('Umbral superado.');

            if ($notify) {
                $this->notifyManagers($rate, $critical, $autoSuppress);
            }
        } elseif (! $isBreached && $wasBreached) {
            $this->info('La tasa de rebote se ha recuperado por debajo del umbral.');
        }

        return self::SUCCESS;
    }

    private function notifyManagers(float $rate, float $critical, bool $autoSuppressed): void
    {
        try {
            $managers = User::permission('manage_helpdesk')->get();
        } catch (Throwable $e) {
            Log::warning('ticket:check-reputation: no se pudieron resolver los managers a notificar', ['error' => $e->getMessage()]);

            return;
        }

        if ($managers->isEmpty()) {
            Log::warning('ticket:check-reputation: no hay usuarios con permiso manage_helpdesk a quien avisar');

            return;
        }

        $subject = '[Helpdesk] Reputación de correo: tasa de rebote elevada';
        $content = '<h2>Reputación del ticket mailer</h2>'
            .'<p>La tasa de rebote de los últimos 30 días es <strong>'.e($rate).'%</strong>, por encima del umbral crítico de '.e($critical).'%.</p>'
            .($autoSuppressed ? '<p>El envío saliente de tickets se ha pausado automáticamente hasta que la tasa se recupere.</p>' : '')
            .'<p>Revísalo en Tickets → Reputación y autenticación.</p>';

        foreach ($managers as $manager) {
            if (empty($manager->email)) {
                continue;
            }

            Mail::to($manager->email, $manager->name ?? null)->queue(new OpsAlertMail($subject, $content));
        }

        $this->warn('Alerta de reputación enviada a '.$managers->count().' manager(s).');
    }
}
