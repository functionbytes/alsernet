<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Mail\SlaDigestMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TeamChannelNotifier;

/**
 * Resumen de tickets con el SLA incumplido para managers y canal del equipo
 * (24-sep-2026), en lugar de un correo y un mensaje por cada incumplimiento.
 * Ver config helpdesktickets.sla_alerts.
 *
 * Se programa cada hora, pero solo envía cuando han pasado `digest_hours`
 * desde el último resumen Y la lista de vencidos ha cambiado: una lista
 * idéntica a la anterior no dice nada nuevo.
 */
class SendSlaDigestCommand extends Command
{
    protected $signature = 'ticket:sla-digest {--force : Enviar aunque no toque o la lista no haya cambiado}';

    protected $description = 'Envía a los managers el resumen de tickets con el SLA incumplido';

    private const LAST_SENT_KEY = 'helpdesktickets:sla-digest:last-sent';

    private const SIGNATURE_KEY = 'helpdesktickets:sla-digest:signature';

    private const LIST_LIMIT = 50;

    public function handle(TeamChannelNotifier $teamChannels): int
    {
        if (! config('helpdesktickets.sla_alerts.managers_digest', true)) {
            $this->info('Resumen desactivado (sla_alerts.managers_digest).');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $hours = max(1, (int) config('helpdesktickets.sla_alerts.digest_hours', 4));
        $lastSent = Cache::get(self::LAST_SENT_KEY);

        if (! $force && $lastSent && now()->diffInHours($lastSent, true) < $hours) {
            $this->info('Aún no toca: último resumen '.$lastSent.'.');

            return self::SUCCESS;
        }

        $query = $this->breachedQuery();
        $total = (clone $query)->count();

        if ($total === 0) {
            Cache::forget(self::SIGNATURE_KEY);
            $this->info('Ningún ticket con el SLA incumplido.');

            return self::SUCCESS;
        }

        $signature = sha1((clone $query)->orderBy('id')->pluck('id')->implode(','));

        if (! $force && Cache::get(self::SIGNATURE_KEY) === $signature) {
            $this->info('La lista de vencidos no ha cambiado desde el último resumen.');

            return self::SUCCESS;
        }

        $tickets = $query->with(['assignee'])
            ->orderBy('sla_resolution_due_at')
            ->limit(self::LIST_LIMIT)
            ->get();

        $subject = $total === 1
            ? '1 ticket con el SLA incumplido'
            : "{$total} tickets con el SLA incumplido";

        $sent = 0;
        foreach ($this->recipients() as $manager) {
            try {
                Mail::to($manager->email)->queue(new SlaDigestMail($subject, $this->html($tickets, $total)));
                $sent++;
            } catch (\Throwable $e) {
                Log::error('SendSlaDigestCommand: no se pudo encolar el resumen', [
                    'user_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $teamChannels->notify('⚠️ '.$subject.': '.$tickets->take(10)->pluck('ticket_number')->implode(', ')
            .($total > 10 ? ' y '.($total - 10).' más' : ''));

        Cache::put(self::LAST_SENT_KEY, now(), now()->addDays(2));
        Cache::put(self::SIGNATURE_KEY, $signature, now()->addDays(2));

        $this->info("Resumen de {$total} tickets enviado a {$sent} managers.");

        return self::SUCCESS;
    }

    /**
     * Abiertos, sin pausa de SLA y con cualquiera de los tres plazos vencido.
     */
    private function breachedQuery()
    {
        return Ticket::query()
            ->whereNull('closed_at')
            ->whereNull('resolved_at')
            ->whereNull('sla_paused_at')
            ->where(fn ($q) => $q->where('sla_resolution_breached', true)
                ->orWhere('sla_first_response_breached', true)
                ->orWhere('sla_next_response_breached', true));
    }

    /** @return Collection<int, User> */
    private function recipients(): Collection
    {
        try {
            return User::permission('manage_helpdesk')->whereNotNull('email')->get();
        } catch (\Throwable) {
            // El permiso no existe en esta instalación: no hay a quién avisar.
            return collect();
        }
    }

    /** @param  Collection<int, Ticket>  $tickets */
    private function html(Collection $tickets, int $total): string
    {
        $rows = $tickets->map(function (Ticket $t): string {
            $url = route('manager.helpdesk.tickets.index', ['ticket' => $t->id, 'quick_filter' => 'all']);
            $due = $t->slaEffectiveDueDate();

            return '<tr>'
                .'<td style="padding:6px 10px;border-bottom:1px solid #e8e8e8"><a href="'.e($url).'">'.e($t->ticket_number).'</a></td>'
                .'<td style="padding:6px 10px;border-bottom:1px solid #e8e8e8">'.e($t->subject ?: '(sin asunto)').'</td>'
                .'<td style="padding:6px 10px;border-bottom:1px solid #e8e8e8">'.e($t->assignee?->full_name ?: 'Sin asignar').'</td>'
                .'<td style="padding:6px 10px;border-bottom:1px solid #e8e8e8">'.e($due ? 'venció '.$due->diffForHumans() : '—').'</td>'
                .'</tr>';
        })->implode('');

        $more = $total > $tickets->count()
            ? '<p>Y '.($total - $tickets->count()).' más en el panel de tickets (pestaña «SLA en riesgo»).</p>'
            : '';

        return '<div style="font-family:Arial,sans-serif;font-size:14px;color:#18181b">'
            .'<p>Estos tickets siguen abiertos con el SLA incumplido:</p>'
            .'<table style="border-collapse:collapse;width:100%"><thead><tr>'
            .'<th align="left" style="padding:6px 10px;border-bottom:2px solid #90bb13">Ticket</th>'
            .'<th align="left" style="padding:6px 10px;border-bottom:2px solid #90bb13">Asunto</th>'
            .'<th align="left" style="padding:6px 10px;border-bottom:2px solid #90bb13">Agente</th>'
            .'<th align="left" style="padding:6px 10px;border-bottom:2px solid #90bb13">Plazo</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table>'
            .$more
            .'<p style="color:#71717a;font-size:12px">Resumen automático cada '
            .(int) config('helpdesktickets.sla_alerts.digest_hours', 4)
            .' horas, solo cuando la lista cambia (HELPDESK_SLA_DIGEST_HOURS).</p>'
            .'</div>';
    }
}
