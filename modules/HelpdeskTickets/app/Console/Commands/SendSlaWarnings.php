<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskTickets\Jobs\SendSlaWarnings as SendSlaWarningsJob;

/**
 * Disparo manual de los avisos de SLA próximos a vencer. Delega en el job
 * canónico (el mismo que corre programado cada 30 min): SlaWarning event →
 * listener SendSlaWarningNotification → SlaWarningMail.
 *
 * Antes este comando DUPLICABA toda la lógica SLA usando una notificación y un
 * Mailable que nunca se crearon (`App\Notifications\Helpdesk\TicketSlaWarning`,
 * `Modules\Mail\Mail\Helpdesk\TicketSlaWarningMail`) → fatal "class not found"
 * al ejecutarse. Consolidado en el job para tener una única fuente de verdad.
 */
class SendSlaWarnings extends Command
{
    protected $signature = 'tickets:sla-warnings';

    protected $description = 'Enviar avisos de SLA próximos a vencer (delega en el job canónico programado).';

    public function handle(): int
    {
        $this->info('Procesando avisos de SLA próximos a vencer...');

        SendSlaWarningsJob::dispatchSync();

        $this->info('Avisos de SLA procesados.');

        return self::SUCCESS;
    }
}
