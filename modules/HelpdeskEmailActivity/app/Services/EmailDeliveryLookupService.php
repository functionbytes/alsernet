<?php

namespace Modules\HelpdeskEmailActivity\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\EmailSuppression;

/**
 * Consulta de entregabilidad reutilizable: responde "¿a esta persona se le
 * envió?, ¿le llegó?, ¿lo abrió?, ¿hizo clic?" sin que el módulo que pregunta
 * tenga que conocer el esquema de email_logs.
 *
 * Existía ya todo el dato (email_logs + email_log_opens + email_log_clicks +
 * email_suppressions) pero solo se podía ver por la interfaz web. Esto lo
 * expone como servicio y como API JSON (EmailDeliveryLookupController) para
 * que cualquier módulo —HelpdeskBirthday, Document, HelpdeskTickets…— pueda
 * pintar el estado de sus propios envíos sin duplicar consultas.
 *
 * Todos los métodos aceptan un `module` opcional para acotar la respuesta a
 * los correos de ese módulo: sin él, "¿abrió el correo?" mezclaría la
 * felicitación de cumpleaños con el aviso de un ticket.
 */
class EmailDeliveryLookupService
{
    /** Ventana por defecto cuando no se pide uuna explícita. */
    private const DEFAULT_DAYS = 90;

    /**
     * Estado agregado de un destinatario: la respuesta a "¿qué ha pasado con
     * los correos que le hemos mandado a esta dirección?".
     *
     * @return array<string, mixed>
     */
    public function forRecipient(string $email, ?string $module = null, ?int $days = null): array
    {
        $email = mb_strtolower(trim($email));

        $query = $this->baseQuery($module, $days)
            ->where('recipients_index', 'like', '%'.$email.'%');

        $summary = $this->summarize($query);
        $summary['email'] = $email;
        $summary['suppressed'] = EmailSuppression::isSuppressed($email, $module);

        return $summary;
    }

    /**
     * Estado de los correos de una entidad concreta (un ticket, un documento,
     * un destinatario de campaña…).
     *
     * @return array<string, mixed>
     */
    public function forEntity(string $entityType, int|string $entityId, ?string $module = null, ?int $days = null): array
    {
        $query = $this->baseQuery($module, $days)
            ->forEntity($entityType, $entityId);

        return $this->summarize($query) + [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ];
    }

    /**
     * Estado de varios destinatarios de una sola consulta. Es lo que evita el
     * N+1 cuando un listado quiere pintar una columna "abierto / no abierto"
     * para cien filas.
     *
     * @param  array<int, string>  $emails
     * @return array<string, array<string, mixed>> indexado por email
     */
    public function forRecipients(array $emails, ?string $module = null, ?int $days = null): array
    {
        $emails = array_values(array_unique(array_map(
            static fn (string $e): string => mb_strtolower(trim($e)),
            array_filter($emails)
        )));

        if ($emails === []) {
            return [];
        }

        $logs = $this->baseQuery($module, $days)
            ->where(function (Builder $q) use ($emails): void {
                foreach ($emails as $email) {
                    $q->orWhere('recipients_index', 'like', '%'.$email.'%');
                }
            })
            ->withCount(['opens', 'clicks'])
            ->get(['id', 'recipients_index', 'status', 'sent_at', 'delivered_at', 'bounced_at', 'suppressed_at']);

        $suppressed = $this->suppressedAmong($emails, $module);

        $result = [];

        foreach ($emails as $email) {
            $own = $logs->filter(
                static fn (EmailLog $log): bool => str_contains((string) $log->recipients_index, $email)
            );

            $result[$email] = $this->summarizeCollection($own) + [
                'email' => $email,
                'suppressed' => isset($suppressed[$email]),
            ];
        }

        return $result;
    }

    /**
     * Agregados de un módulo para pintar un panel de estadísticas.
     *
     * @return array<string, mixed>
     */
    public function statsForModule(string $module, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $from ??= CarbonImmutable::now()->subDays(self::DEFAULT_DAYS)->startOfDay();
        $to ??= CarbonImmutable::now()->endOfDay();

        $logs = EmailLog::query()
            ->forModule($module)
            ->whereBetween('created_at', [$from, $to])
            ->withCount(['opens', 'clicks'])
            ->get(['id', 'status', 'sent_at', 'delivered_at', 'bounced_at', 'suppressed_at']);

        $summary = $this->summarizeCollection($logs);

        return $summary + [
            'module' => $module,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'open_rate' => $this->rate($summary['opened'], $summary['sent']),
            'click_rate' => $this->rate($summary['clicked'], $summary['sent']),
            'bounce_rate' => $this->rate($summary['bounced'], $summary['sent']),
        ];
    }

    /**
     * Los correos concretos que se le enviaron a una dirección, para pintar un
     * historial. Cada fila lleva ya resuelto si se abrió y si se hizo clic.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function historyForRecipient(string $email, ?string $module = null, ?int $days = null, int $limit = 50): Collection
    {
        $email = mb_strtolower(trim($email));

        return $this->baseQuery($module, $days)
            ->where('recipients_index', 'like', '%'.$email.'%')
            ->withCount(['opens', 'clicks'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (EmailLog $log): array => [
                'uid' => $log->uid,
                'subject' => $log->subject,
                'module' => $log->module,
                'status' => $log->status instanceof EmailStatus ? $log->status->value : $log->status,
                'sent_at' => $log->sent_at?->toIso8601String(),
                'delivered_at' => $log->delivered_at?->toIso8601String(),
                'bounced_at' => $log->bounced_at?->toIso8601String(),
                'opened' => $log->opens_count > 0,
                'opens' => $log->opens_count,
                'clicked' => $log->clicks_count > 0,
                'clicks' => $log->clicks_count,
            ]);
    }

    /* ── Interno ─────────────────────────────────────────────────────────── */

    private function baseQuery(?string $module, ?int $days): Builder
    {
        $query = EmailLog::query();

        if ($module !== null && $module !== '') {
            $query->forModule($module);
        }

        $days ??= self::DEFAULT_DAYS;

        if ($days > 0) {
            $query->where('created_at', '>=', CarbonImmutable::now()->subDays($days));
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function summarize(Builder $query): array
    {
        $logs = (clone $query)
            ->withCount(['opens', 'clicks'])
            ->get(['id', 'status', 'sent_at', 'delivered_at', 'bounced_at', 'suppressed_at']);

        return $this->summarizeCollection($logs);
    }

    /**
     * @param  Collection<int, EmailLog>  $logs
     * @return array<string, mixed>
     */
    private function summarizeCollection(Collection $logs): array
    {
        $statusIs = static fn (EmailLog $log, EmailStatus $status): bool => ($log->status instanceof EmailStatus
            ? $log->status
            : EmailStatus::tryFrom((string) $log->status)) === $status;

        $sent = $logs->filter(static fn (EmailLog $l): bool => $l->sent_at !== null)->count();
        $opened = $logs->filter(static fn (EmailLog $l): bool => $l->opens_count > 0)->count();
        $clicked = $logs->filter(static fn (EmailLog $l): bool => $l->clicks_count > 0)->count();

        return [
            'total' => $logs->count(),
            'sent' => $sent,
            'delivered' => $logs->filter(static fn (EmailLog $l): bool => $l->delivered_at !== null)->count(),
            'opened' => $opened,
            'clicked' => $clicked,
            'bounced' => $logs->filter(static fn (EmailLog $l): bool => $l->bounced_at !== null)->count(),
            'failed' => $logs->filter(fn (EmailLog $l): bool => $statusIs($l, EmailStatus::Failed))->count(),
            'queued' => $logs->filter(fn (EmailLog $l): bool => $statusIs($l, EmailStatus::Queued))->count(),
            // Los tres booleanos que responden la pregunta de negocio directa.
            'was_sent' => $sent > 0,
            'was_opened' => $opened > 0,
            'was_clicked' => $clicked > 0,
            'last_sent_at' => $logs->max('sent_at')?->toIso8601String(),
            'last_delivered_at' => $logs->max('delivered_at')?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, string>  $emails
     * @return array<string, true>
     */
    private function suppressedAmong(array $emails, ?string $module): array
    {
        $found = [];

        foreach (array_chunk($emails, 500) as $chunk) {
            $rows = EmailSuppression::query()
                ->whereIn('email', $chunk)
                ->whereIn('module', array_filter(['', (string) $module]))
                ->pluck('email');

            foreach ($rows as $email) {
                $found[mb_strtolower($email)] = true;
            }
        }

        return $found;
    }

    private function rate(int $part, int $whole): float
    {
        return $whole > 0 ? round(($part / $whole) * 100, 1) : 0.0;
    }
}
