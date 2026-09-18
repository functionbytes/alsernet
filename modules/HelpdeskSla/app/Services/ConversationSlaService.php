<?php

namespace Modules\HelpdeskSla\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\SlaPolicy;
use Modules\HelpdeskSla\Events\SlaWarningThreshold;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;

/**
 * Central SLA engine for Helpdesk conversations.
 *
 * Frontera entre los dos motores SLA del producto:
 *
 * - CONVERSACIONES → este servicio (HelpdeskSla): registra incumplimientos en
 *   helpdesk_conversation_sla_breaches y emite avisos/broadcasts; NO escala
 *   prioridades.
 * - TICKETS → Modules\HelpdeskTickets\Services\EscalationService (+ SlaService):
 *   escala prioridades y registra sus incumplimientos en
 *   helpdesk_ticket_sla_breaches.
 *
 * Los motores NO se unifican a propósito (ver docs/helpdesk-sla-boundary.md):
 * cada uno es dueño de su entidad y de su tabla de breaches.
 *
 * Reuses the canonical Modules\Helpdesk\Models\SlaPolicy (hours-based, routed by
 * priority/category) and the real helpdesk_business_hours calendar. Replaces the
 * legacy hardcoded 15-minute command and the broken core listener.
 */
class ConversationSlaService
{
    private const PRIORITY_CACHE_KEY = 'helpdesksla:priority_slug_map';

    private const DEFAULT_WARNING_THRESHOLD = 80;

    /**
     * Columnas mínimas para el barrido de avisos: la misma conexión sirve al
     * inbox en vivo, así que no se hidrata la fila completa cada 15 minutos.
     */
    private const SLA_WARNING_COLUMNS = [
        'id',
        'sla_policy_id',
        'created_at',
        'first_response_at',
        'sla_first_response_due_at',
        'sla_first_response_breached',
        'sla_resolution_due_at',
        'sla_resolution_breached',
        'sla_paused_duration_minutes',
    ];

    public function __construct(
        private readonly BusinessHoursCalculator $businessHours,
    ) {}

    /**
     * Conversation.priority is stored in English (low/normal/high/urgent) while
     * helpdesk_priorities.slug is in Spanish (baja/normal/alta/urgente/critico).
     * This aliases the English value to the canonical slug used by the policies.
     */
    private const PRIORITY_SLUG_ALIASES = [
        'low' => 'baja',
        'normal' => 'normal',
        'high' => 'alta',
        'urgent' => 'urgente',
        'critical' => 'critico',
    ];

    /**
     * Resolve the SLA policy applicable to a conversation.
     *
     * Conversations have no category, so we match an active policy by the
     * conversation priority (priority_id with category_id NULL) and fall back to
     * a global policy (priority_id NULL, category_id NULL).
     */
    public function getApplicablePolicy(Conversation $conversation): ?SlaPolicy
    {
        $priorityId = $this->priorityIdForSlug($conversation->priority);

        if ($priorityId !== null) {
            $policy = SlaPolicy::query()
                ->active()
                ->where('priority_id', $priorityId)
                ->whereNull('category_id')
                ->first();

            if ($policy) {
                return $policy;
            }
        }

        return SlaPolicy::query()
            ->active()
            ->whereNull('priority_id')
            ->whereNull('category_id')
            ->first();
    }

    /**
     * Initialize SLA tracking for a conversation. Idempotent: skips when a policy
     * has already been applied.
     */
    public function initialize(Conversation $conversation): void
    {
        if ($conversation->sla_policy_id !== null) {
            return;
        }

        $policy = $this->getApplicablePolicy($conversation);

        if (! $policy) {
            return;
        }

        $start = $conversation->created_at ?? now();
        $businessOnly = (bool) $policy->business_hours_only;

        $updates = ['sla_policy_id' => $policy->id];

        if ($policy->first_response_time_hours) {
            $updates['sla_first_response_due_at'] = $this->addBusinessHours($start, (int) $policy->first_response_time_hours, $businessOnly);
        }

        if ($policy->resolution_time_hours) {
            $updates['sla_resolution_due_at'] = $this->addBusinessHours($start, (int) $policy->resolution_time_hours, $businessOnly);
        }

        $conversation->updateQuietly($updates);
    }

    /**
     * Mark the first agent response, stopping the first-response clock. Targeted
     * update so it never fires model events and is idempotent.
     */
    public function markFirstResponse(int $conversationId): void
    {
        Conversation::query()
            ->whereKey($conversationId)
            ->whereNull('first_response_at')
            ->update(['first_response_at' => now()]);
    }

    /**
     * Detect first-response and resolution breaches across open conversations,
     * persist a breach record, flag the conversation and broadcast SlaBreached.
     *
     * @return int number of breaches detected
     */
    public function checkBreaches(): int
    {
        $this->autoResumeStaleSnoozes();

        $count = 0;

        Conversation::query()
            ->open()
            ->whereNull('first_response_at')
            ->where('sla_first_response_breached', false)
            ->whereNotNull('sla_first_response_due_at')
            ->where('sla_first_response_due_at', '<', now())
            ->whereNull('sla_paused_at')
            ->cursor()
            ->each(function (Conversation $conversation) use (&$count): void {
                // Breach record + conversation flag must be atomic: a half-write
                // (record without flag) would be re-detected next run and produce
                // duplicate breach records.
                DB::connection('helpdesk')->transaction(function () use ($conversation): void {
                    $this->recordBreach($conversation, ConversationSlaBreach::TYPE_FIRST_RESPONSE, $conversation->sla_first_response_due_at);

                    $conversation->updateQuietly([
                        'sla_first_response_breached' => true,
                        'sla_warned_at' => $conversation->sla_warned_at ?? now(),
                    ]);
                });

                SlaBreached::dispatch($conversation);
                $count++;
            });

        Conversation::query()
            ->open()
            ->where('sla_resolution_breached', false)
            ->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<', now())
            ->whereNull('sla_paused_at')
            ->cursor()
            ->each(function (Conversation $conversation) use (&$count): void {
                DB::connection('helpdesk')->transaction(function () use ($conversation): void {
                    $this->recordBreach($conversation, ConversationSlaBreach::TYPE_RESOLUTION, $conversation->sla_resolution_due_at);

                    $conversation->updateQuietly(['sla_resolution_breached' => true]);
                });

                SlaBreached::dispatch($conversation);
                $count++;
            });

        if ($count > 0) {
            Log::info('HelpdeskSla: check de incumplimientos completado.', ['breaches' => $count]);
        }

        return $count;
    }

    /**
     * Red de seguridad: si UnsnoozeConversationJob se pierde (cola caída, fallo
     * silencioso), la conversación queda pausada para siempre y desaparece del
     * radar de SLA. Antes de cada check de incumplimientos, se reanuda
     * cualquier conversación pausada cuyo snoozed_until ya no esté vigente.
     */
    private function autoResumeStaleSnoozes(): void
    {
        Conversation::query()
            ->open()
            ->whereNotNull('sla_paused_at')
            ->where(function (Builder $query): void {
                $query->whereNull('snoozed_until')
                    ->orWhere('snoozed_until', '<=', now());
            })
            ->cursor()
            ->each(fn (Conversation $conversation) => $this->resumeSla($conversation));
    }

    /**
     * Warn about open conversations approaching their SLA deadline (past the
     * policy's warning_threshold_percent but not yet breached). Warns once per
     * conversation, deduped via sla_warned_at.
     *
     * @return int number of warnings sent
     */
    public function sendWarnings(): int
    {
        $activePolicies = SlaPolicy::query()->active()->get();
        $count = 0;

        foreach ($activePolicies as $policy) {
            $count += $this->sendWarningsForPolicy($policy);
        }

        $count += $this->sendWarningsForOrphanedPolicies($activePolicies->pluck('id'));

        if ($count > 0) {
            Log::info('HelpdeskSla: avisos de SLA enviados.', ['warnings' => $count]);
        }

        return $count;
    }

    /**
     * Conversaciones cuya policy sigue activa: el umbral de aviso se traduce a
     * SQL para que solo lleguen a PHP las conversaciones que de verdad podrían
     * estar cerca del límite, en vez de hidratar todo el backlog abierto.
     */
    private function sendWarningsForPolicy(SlaPolicy $policy): int
    {
        $threshold = (int) ($policy->warning_threshold_percent ?? self::DEFAULT_WARNING_THRESHOLD);
        $count = 0;

        $this->baseWarningQuery()
            ->where('sla_policy_id', $policy->id)
            ->where(fn (Builder $query) => $this->applyThresholdFilter($query, $policy, $threshold))
            ->cursor()
            ->each(function (Conversation $conversation) use (&$count, $policy, $threshold): void {
                $count += $this->dispatchWarningIfApproaching($conversation, $policy, $threshold);
            });

        return $count;
    }

    /**
     * Conversaciones cuya policy ya no está activa (desactivada/borrada
     * después de aplicarse): se conservan con el umbral por defecto para no
     * perder cobertura en silencio. La policy se resuelve una única vez por
     * id y el resultado null también se cachea (array_key_exists, no ??=)
     * para no repetir la consulta por cada conversación huérfana.
     */
    private function sendWarningsForOrphanedPolicies(Collection $activePolicyIds): int
    {
        $count = 0;
        $resolved = [];

        $this->baseWarningQuery()
            ->whereNotIn('sla_policy_id', $activePolicyIds)
            ->cursor()
            ->each(function (Conversation $conversation) use (&$count, &$resolved): void {
                $policyId = $conversation->sla_policy_id;

                if (! array_key_exists($policyId, $resolved)) {
                    $resolved[$policyId] = SlaPolicy::find($policyId);
                }

                $policy = $resolved[$policyId];
                $threshold = (int) ($policy->warning_threshold_percent ?? self::DEFAULT_WARNING_THRESHOLD);

                $count += $this->dispatchWarningIfApproaching($conversation, $policy, $threshold);
            });

        return $count;
    }

    private function baseWarningQuery(): Builder
    {
        return Conversation::query()
            ->select(self::SLA_WARNING_COLUMNS)
            ->open()
            ->whereNull('sla_warned_at')
            ->whereNotNull('sla_policy_id')
            ->whereNull('sla_paused_at');
    }

    private function applyThresholdFilter(Builder $query, SlaPolicy $policy, int $threshold): void
    {
        $businessOnly = (bool) $policy->business_hours_only;

        $query->where(function (Builder $q) use ($policy, $threshold, $businessOnly): void {
            $q->whereNull('first_response_at')
                ->where('sla_first_response_breached', false)
                ->whereNotNull('sla_first_response_due_at')
                ->where('sla_first_response_due_at', '>', now())
                ->when(
                    $policy->first_response_time_hours,
                    fn (Builder $q2) => $this->constrainToThreshold($q2, 'sla_first_response_due_at', (int) $policy->first_response_time_hours, $threshold, $businessOnly)
                );
        })->orWhere(function (Builder $q) use ($policy, $threshold, $businessOnly): void {
            $q->where('sla_resolution_breached', false)
                ->whereNotNull('sla_resolution_due_at')
                ->where('sla_resolution_due_at', '>', now())
                ->when(
                    $policy->resolution_time_hours,
                    fn (Builder $q2) => $this->constrainToThreshold($q2, 'sla_resolution_due_at', (int) $policy->resolution_time_hours, $threshold, $businessOnly)
                );
        });
    }

    /**
     * Pre-filtro SQL del umbral de aviso.
     *
     * - Política en horas naturales: fórmula exacta sobre la fecha límite real
     *   (due_at - created_at son literalmente las horas de la política).
     * - Política en horas hábiles: due_at ya viene estirado por fines de
     *   semana/festivos, así que usar esa ventana infravaloraría el % real
     *   consumido. Se compara contra las horas "puras" de la política: como
     *   el tiempo hábil consumido siempre es <= al tiempo natural
     *   transcurrido, esto nunca descarta una conversación que sí haya
     *   cruzado el umbral real (el filtrado preciso lo hace después
     *   approachingWarning(), consciente de horas hábiles, en PHP).
     */
    private function constrainToThreshold(Builder $query, string $dueColumn, int $hours, int $threshold, bool $businessOnly): Builder
    {
        if ($businessOnly) {
            $elapsedSeconds = (int) round($hours * 3600 * $threshold / 100);

            return $query->whereRaw('TIMESTAMPDIFF(SECOND, created_at, NOW()) >= ?', [$elapsedSeconds]);
        }

        return $query->whereRaw(
            "TIMESTAMPDIFF(SECOND, created_at, NOW()) >= TIMESTAMPDIFF(SECOND, created_at, {$dueColumn}) * ? / 100",
            [$threshold]
        );
    }

    private function dispatchWarningIfApproaching(Conversation $conversation, ?SlaPolicy $policy, int $threshold): int
    {
        $approaching = $this->approachingWarning($conversation, $policy, $threshold);

        if ($approaching === null) {
            return 0;
        }

        [$type, $percent] = $approaching;

        $conversation->updateQuietly(['sla_warned_at' => now()]);
        SlaWarningThreshold::dispatch($conversation, $type, $percent);

        return 1;
    }

    /**
     * Recompute SLA due dates after a priority change. Re-resolves the policy,
     * recomputes due dates from the creation time and re-evaluates breach flags.
     * No-op on closed conversations and on conversations currently paused (their
     * due dates are frozen until resumeSla() extends them).
     */
    public function recalculate(Conversation $conversation): void
    {
        if ($conversation->closed_at !== null) {
            return;
        }

        $policy = $this->getApplicablePolicy($conversation);

        if (! $policy) {
            return;
        }

        if ($conversation->sla_paused_at !== null) {
            return;
        }

        $start = $conversation->created_at ?? now();
        $businessOnly = (bool) $policy->business_hours_only;
        $pausedMinutes = (int) ($conversation->sla_paused_duration_minutes ?? 0);

        $updates = [
            'sla_policy_id' => $policy->id,
            'sla_warned_at' => null,
        ];

        if ($policy->first_response_time_hours && $conversation->first_response_at === null) {
            $dueAt = $this->addBusinessHours($start, (int) $policy->first_response_time_hours, $businessOnly)
                ->addMinutes($pausedMinutes);

            $updates['sla_first_response_due_at'] = $dueAt;
            $updates['sla_first_response_breached'] = $this->reconcileBreachFlag(
                $conversation,
                ConversationSlaBreach::TYPE_FIRST_RESPONSE,
                (bool) $conversation->sla_first_response_breached,
                $dueAt
            );
        }

        if ($policy->resolution_time_hours) {
            $dueAt = $this->addBusinessHours($start, (int) $policy->resolution_time_hours, $businessOnly)
                ->addMinutes($pausedMinutes);

            $updates['sla_resolution_due_at'] = $dueAt;
            $updates['sla_resolution_breached'] = $this->reconcileBreachFlag(
                $conversation,
                ConversationSlaBreach::TYPE_RESOLUTION,
                (bool) $conversation->sla_resolution_breached,
                $dueAt
            );
        }

        $conversation->updateQuietly($updates);
    }

    /**
     * Al recalcular tras un cambio de política/prioridad, un incumplimiento ya
     * registrado no puede resetearse a "no incumplido" sin resolver su
     * registro: de lo contrario el próximo check-breaches lo vuelve a detectar
     * y genera un segundo registro + una segunda alerta. Solo se desactiva el
     * flag cuando la nueva fecha límite calculada queda en el futuro; si sigue
     * en el pasado, el incumplimiento (y su registro) se dejan tal cual.
     */
    private function reconcileBreachFlag(Conversation $conversation, string $type, bool $wasBreached, Carbon $newDueAt): bool
    {
        if (! $wasBreached) {
            return false;
        }

        if ($newDueAt->isPast()) {
            return true;
        }

        ConversationSlaBreach::query()
            ->where('conversation_id', $conversation->id)
            ->ofType($type)
            ->unresolved()
            ->update(['resolved' => true, 'resolved_at' => now()]);

        return false;
    }

    /**
     * Finalize SLA tracking when a conversation is closed: record a resolution
     * breach if it was closed after its due date, and resolve pending breaches.
     */
    public function finalize(Conversation $conversation): void
    {
        $closedAt = $conversation->closed_at;

        if (
            $closedAt
            && $conversation->sla_resolution_due_at
            && ! $conversation->sla_resolution_breached
            && $closedAt->greaterThan($conversation->sla_resolution_due_at)
        ) {
            $this->recordBreach($conversation, ConversationSlaBreach::TYPE_RESOLUTION, $conversation->sla_resolution_due_at);
            $conversation->updateQuietly(['sla_resolution_breached' => true]);
        }

        ConversationSlaBreach::query()
            ->where('conversation_id', $conversation->id)
            ->unresolved()
            ->update(['resolved' => true, 'resolved_at' => now()]);
    }

    /**
     * Pause the SLA clock (e.g. while snoozed / waiting for the customer).
     */
    public function pauseSla(Conversation $conversation): void
    {
        if ($conversation->sla_paused_at !== null) {
            return;
        }

        $conversation->updateQuietly(['sla_paused_at' => now()]);
    }

    /**
     * Resume the SLA clock, extending due dates by the paused duration.
     */
    public function resumeSla(Conversation $conversation): void
    {
        if ($conversation->sla_paused_at === null) {
            return;
        }

        $pausedMinutes = (int) round(abs($conversation->sla_paused_at->diffInMinutes(now())));

        $updates = [
            'sla_paused_duration_minutes' => ($conversation->sla_paused_duration_minutes ?? 0) + $pausedMinutes,
            'sla_paused_at' => null,
        ];

        if ($conversation->sla_first_response_due_at) {
            $updates['sla_first_response_due_at'] = $conversation->sla_first_response_due_at->copy()->addMinutes($pausedMinutes);
        }

        if ($conversation->sla_resolution_due_at) {
            $updates['sla_resolution_due_at'] = $conversation->sla_resolution_due_at->copy()->addMinutes($pausedMinutes);
        }

        $conversation->updateQuietly($updates);
    }

    /**
     * Determine which SLA dimension is past the warning threshold, if any.
     * First response takes precedence over resolution.
     *
     * @return array{0: string, 1: int}|null [type, percentUsed]
     */
    private function approachingWarning(Conversation $conversation, ?SlaPolicy $policy, int $threshold): ?array
    {
        $created = $conversation->created_at;

        if (! $created) {
            return null;
        }

        $businessOnly = (bool) ($policy?->business_hours_only);
        $pausedMinutes = (int) ($conversation->sla_paused_duration_minutes ?? 0);

        if (
            $conversation->first_response_at === null
            && ! $conversation->sla_first_response_breached
            && $conversation->sla_first_response_due_at?->isFuture()
        ) {
            $percent = $this->percentUsed($created, $conversation->sla_first_response_due_at, $businessOnly, $pausedMinutes);

            if ($percent >= $threshold) {
                return [ConversationSlaBreach::TYPE_FIRST_RESPONSE, $percent];
            }
        }

        if (
            ! $conversation->sla_resolution_breached
            && $conversation->sla_resolution_due_at?->isFuture()
        ) {
            $percent = $this->percentUsed($created, $conversation->sla_resolution_due_at, $businessOnly, $pausedMinutes);

            if ($percent >= $threshold) {
                return [ConversationSlaBreach::TYPE_RESOLUTION, $percent];
            }
        }

        return null;
    }

    /**
     * % del plazo consumido entre $created y $due. Cuando la política es de
     * horas hábiles, se miden minutos hábiles reales (BusinessHoursCalculator)
     * en vez de tiempo natural: si no, el aviso se "quema" durante un fin de
     * semana sin que haya transcurrido tiempo hábil real. Los minutos en que
     * el reloj estuvo pausado (snooze) tampoco cuentan como consumidos.
     */
    private function percentUsed(Carbon $created, Carbon $due, bool $businessHoursOnly, int $pausedMinutes = 0): int
    {
        $now = now();

        if ($businessHoursOnly) {
            $total = $this->businessHours->businessMinutesBetween($created, $due);
            $used = $this->businessHours->businessMinutesBetween($created, $now);
        } else {
            $total = abs($created->diffInMinutes($due));
            $used = abs($created->diffInMinutes($now));
        }

        $used = max(0, $used - $pausedMinutes);

        if ($total <= 0) {
            return 100;
        }

        return (int) min(100, round($used / $total * 100));
    }

    private function recordBreach(Conversation $conversation, string $type, ?Carbon $dueAt): ConversationSlaBreach
    {
        $minutesOver = $dueAt ? (int) round(abs($dueAt->diffInMinutes(now()))) : null;

        return ConversationSlaBreach::create([
            'conversation_id' => $conversation->id,
            'sla_type' => $type,
            'due_at' => $dueAt,
            'breached_at' => now(),
            'minutes_over' => $minutesOver,
        ]);
    }

    private function priorityIdForSlug(?string $slug): ?int
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $map = Cache::remember(self::PRIORITY_CACHE_KEY, 300, function (): array {
            return DB::connection('helpdesk')
                ->table('helpdesk_priorities')
                ->pluck('id', 'slug')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        });

        // Try the raw value first, then the English->Spanish slug alias.
        $alias = self::PRIORITY_SLUG_ALIASES[$slug] ?? null;

        return $map[$slug] ?? ($alias !== null ? ($map[$alias] ?? null) : null);
    }

    /**
     * Add a number of hours to a start date, optionally honouring the configured
     * business-hours calendar. El algoritmo vive en BusinessHoursCalculator
     * (compartido con el escalado de tickets); aquí solo se delega.
     */
    private function addBusinessHours(Carbon|string $start, int $hours, bool $businessHoursOnly): Carbon
    {
        return $this->businessHours->addBusinessHours($start, $hours, $businessHoursOnly);
    }
}
