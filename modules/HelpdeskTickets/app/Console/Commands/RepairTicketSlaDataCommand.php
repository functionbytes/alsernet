<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketItem;

/**
 * Corrige los datos que dejaron los bugs arreglados el 24-sep-2026 (commit
 * b159a73e4). Reversible: guarda antes los valores originales en
 * storage/app/helpdesk-repairs/ y --restore=<fichero> los devuelve.
 *
 * 1. Prioridad inflada por el escalado: vuelve a la que tenía antes del
 *    primer escalado (TicketHistory 'escalated', metadata.old_priority),
 *    solo si nadie la cambió a mano después, y reinicia los contadores de
 *    escalado para que las reglas nuevas decidan desde cero.
 * 2. first_response_at puesto por la respuesta automática: pasa a la primera
 *    respuesta pública real de un agente, o a null si no la hay.
 * 3. Plazo de "siguiente respuesta": solo existe mientras hay una réplica del
 *    cliente sin contestar; su marca de incumplido se recalcula igual.
 */
class RepairTicketSlaDataCommand extends Command
{
    protected $signature = 'ticket:repair-sla-data
        {--dry-run : Solo muestra lo que cambiaría}
        {--restore= : Ruta (dentro de storage/app) de un respaldo a restaurar}';

    protected $description = 'Corrige prioridades escaladas de más y datos de SLA erróneos (reversible)';

    private const FIELDS = [
        'priority', 'escalation_count', 'escalated_at', 'first_response_at',
        'sla_next_response_due_at', 'sla_next_response_breached',
    ];

    public function handle(): int
    {
        if ($restore = $this->option('restore')) {
            return $this->restore($restore);
        }

        $changes = [];

        Ticket::query()->withTrashed()->with('slaPolicy')->orderBy('id')->chunkById(200, function ($tickets) use (&$changes) {
            foreach ($tickets as $ticket) {
                $new = $this->repairedValues($ticket);
                $diff = [];

                foreach ($new as $field => $value) {
                    $old = $ticket->getRawOriginal($field);
                    if ($this->normalize($old) !== $this->normalize($value)) {
                        $diff[$field] = ['old' => $old, 'new' => $value];
                    }
                }

                if ($diff !== []) {
                    $changes[$ticket->id] = $diff;
                }
            }
        });

        $this->summary($changes);

        if ($this->option('dry-run') || $changes === []) {
            $this->info($changes === [] ? 'Nada que corregir.' : 'Simulación: no se ha cambiado nada.');

            return Command::SUCCESS;
        }

        $path = 'helpdesk-repairs/sla-repair-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode($changes, JSON_PRETTY_PRINT));

        DB::connection('helpdesk')->transaction(function () use ($changes) {
            foreach ($changes as $id => $diff) {
                DB::connection('helpdesk')->table('helpdesk_tickets')->where('id', $id)
                    ->update(array_map(fn ($c) => $c['new'], $diff));
            }
        });

        $this->info('Corregidos '.count($changes).' tickets. Respaldo: storage/app/'.$path);
        $this->line('Para deshacer: php artisan ticket:repair-sla-data --restore='.$path);

        return Command::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function repairedValues(Ticket $ticket): array
    {
        $values = [];

        // 1. Prioridad antes del primer escalado.
        $escalations = TicketHistory::query()
            ->where('ticket_id', $ticket->id)
            ->where('action_type', 'escalated')
            ->orderBy('id')
            ->get(['metadata']);

        if ($escalations->isNotEmpty()) {
            $first = $escalations->first()->metadata['old_priority'] ?? null;
            $last = $escalations->last()->metadata['new_priority'] ?? null;

            // Si la prioridad actual no es la que dejó el último escalado,
            // alguien la cambió a mano después: se respeta.
            if ($first && $last && $ticket->priority === $last) {
                $values['priority'] = $first;
                $values['escalation_count'] = 0;
                $values['escalated_at'] = null;
            }
        }

        // 2. Primera respuesta real de un agente.
        $firstAgentReply = TicketItem::query()
            ->where('ticket_id', $ticket->id)
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNotNull('user_id')
            ->min('created_at');
        $values['first_response_at'] = $firstAgentReply;

        // 3. Siguiente respuesta: solo con una réplica del cliente pendiente
        //    de contestar y el ticket sin cerrar.
        // El plazo depende del multiplicador de la prioridad: se calcula con
        // la prioridad ya corregida del paso 1, no con la inflada.
        if (isset($values['priority'])) {
            $ticket->priority = $values['priority'];
        }

        $due = null;
        if ($firstAgentReply && $ticket->closed_at === null && $ticket->resolved_at === null) {
            $lastPublic = TicketItem::query()
                ->where('ticket_id', $ticket->id)
                ->where('type', 'message')
                ->where('is_internal', false)
                ->where(fn ($q) => $q->whereNotNull('user_id')->orWhereNotNull('author_id'))
                ->orderByDesc('id')
                ->first(['user_id', 'author_id', 'created_at']);

            if ($lastPublic && $lastPublic->user_id === null && $lastPublic->author_id !== null) {
                $due = $ticket->nextResponseDueFrom($lastPublic->created_at);
            }
        }
        $values['sla_next_response_due_at'] = $due?->toDateTimeString();
        $values['sla_next_response_breached'] = $due !== null && $due->isPast() ? 1 : 0;

        return $values;
    }

    private function normalize(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) ($value ?? '');
    }

    /** @param  array<int, array<string, array{old: mixed, new: mixed}>>  $changes */
    private function summary(array $changes): void
    {
        $counts = array_fill_keys(self::FIELDS, 0);

        foreach ($changes as $diff) {
            foreach (array_keys($diff) as $field) {
                $counts[$field]++;
            }
        }

        $this->table(['Campo', 'Tickets'], collect($counts)->map(fn ($n, $f) => [$f, $n])->values()->all());
        $this->line('Tickets afectados: '.count($changes));
    }

    private function restore(string $path): int
    {
        if (! Storage::disk('local')->exists($path)) {
            $this->error("No existe storage/app/{$path}");

            return Command::FAILURE;
        }

        $changes = json_decode(Storage::disk('local')->get($path), true) ?: [];

        DB::connection('helpdesk')->transaction(function () use ($changes) {
            foreach ($changes as $id => $diff) {
                DB::connection('helpdesk')->table('helpdesk_tickets')->where('id', $id)
                    ->update(array_map(fn ($c) => $c['old'], $diff));
            }
        });

        $this->info('Restaurados '.count($changes).' tickets desde storage/app/'.$path);

        return Command::SUCCESS;
    }
}
