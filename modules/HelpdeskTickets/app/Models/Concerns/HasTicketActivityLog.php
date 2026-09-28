<?php

namespace Modules\HelpdeskTickets\Models\Concerns;

use App\Models\User;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Spatie\Activitylog\LogOptions;

trait HasTicketActivityLog
{
    /**
     * Memo por petición de activityAssigneeLabel() para los asignatarios que
     * ya no salen en CatalogCacheService::agents().
     *
     * @var array<int, string>
     */
    private static array $assigneeLabelCache = [];

    /**
     * Get activity log options configuration.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(self::ACTIVITY_LOGGED_ATTRIBUTES)
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $eventName) {
                // $this aquí es el propio Ticket que disparó el evento: un
                // closure "function(){}" (no arrow, no static) definido
                // dentro de un método de instancia se vincula
                // automáticamente a $this en PHP — no hace falta bindTo().
                return match ($eventName) {
                    'created' => 'Ticket creado',
                    'updated' => $this->describeUpdateEvent(),
                    'deleted' => 'Ticket eliminado',
                    default => $eventName,
                };
            })
            ->dontSubmitEmptyLogs();
    }

    /**
     * Descripción específica del cambio para la pestaña Actividad, a partir
     * del diff real que LogsActivity ya calcula vía logOnly()/logOnlyDirty()
     * — antes se descartaba por completo y la pestaña mostraba el mismo
     * texto estático "Ticket actualizado" en el 100% de las entradas de
     * cualquier ticket con más de un cambio, indistinguibles entre sí salvo
     * por el timestamp (bug real de QA: 20 y 12 entradas idénticas en 2
     * tickets de prueba).
     *
     * getDirty()/getOriginal() siguen siendo válidos aquí: el evento
     * Eloquent 'updated' (que dispara este closure vía
     * LogsActivity::bootLogsActivity()) se lanza DESDE
     * Model::performUpdate(), DESPUÉS del UPDATE en BD pero ANTES de
     * Model::finishSave()->syncOriginal() — el snapshot "original" todavía
     * tiene los valores previos al guardado.
     */
    private function describeUpdateEvent(): string
    {
        $dirty = array_intersect_key($this->getDirty(), array_flip(self::ACTIVITY_LOGGED_ATTRIBUTES));

        if ($dirty === []) {
            return 'Ticket actualizado';
        }

        $parts = [];

        if (array_key_exists('priority', $dirty)) {
            $parts[] = "Prioridad cambiada de '{$this->activityPriorityLabel($this->getOriginal('priority'))}' a '{$this->activityPriorityLabel($dirty['priority'])}'";
        }
        if (array_key_exists('status_id', $dirty)) {
            $parts[] = "Estado cambiado de '{$this->activityStatusLabel($this->getOriginal('status_id'))}' a '{$this->activityStatusLabel($dirty['status_id'])}'";
        }
        if (array_key_exists('category_id', $dirty)) {
            $parts[] = "Categoría cambiada de '{$this->activityCategoryLabel($this->getOriginal('category_id'))}' a '{$this->activityCategoryLabel($dirty['category_id'])}'";
        }
        if (array_key_exists('assignee_id', $dirty)) {
            $parts[] = "Asignación cambiada de '{$this->activityAssigneeLabel($this->getOriginal('assignee_id'))}' a '{$this->activityAssigneeLabel($dirty['assignee_id'])}'";
        }
        if (array_key_exists('group_id', $dirty)) {
            $parts[] = "Equipo cambiado de '{$this->activityGroupLabel($this->getOriginal('group_id'))}' a '{$this->activityGroupLabel($dirty['group_id'])}'";
        }
        if (array_key_exists('subject', $dirty)) {
            $parts[] = 'Asunto actualizado';
        }
        if (array_key_exists('tags', $dirty)) {
            $parts[] = 'Etiquetas actualizadas';
        }
        if (array_key_exists('custom_fields', $dirty)) {
            $parts[] = 'Campos personalizados actualizados';
        }
        if (array_key_exists('closed_at', $dirty)) {
            $parts[] = $dirty['closed_at'] ? 'Ticket cerrado' : 'Ticket reabierto';
        }
        if (array_key_exists('resolved_at', $dirty)) {
            $parts[] = $dirty['resolved_at'] ? 'Ticket marcado como resuelto' : 'Resolución de ticket revertida';
        }

        // Fallback: campo vigilado mutó pero no encaja en ningún caso de
        // arriba (no debería pasar salvo que ACTIVITY_LOGGED_ATTRIBUTES
        // crezca sin actualizar este método) — mismo texto genérico de
        // siempre en vez de una descripción vacía.
        return $parts === [] ? 'Ticket actualizado' : implode('; ', $parts);
    }

    private function activityPriorityLabel(mixed $value): string
    {
        return match ($value) {
            'urgent' => 'Urgente',
            'high' => 'Alta',
            'normal' => 'Normal',
            'low' => 'Baja',
            default => $value !== null ? (string) $value : '—',
        };
    }

    private function activityStatusLabel(mixed $id): string
    {
        if (! $id) {
            return '—';
        }

        return CatalogCacheService::statuses()->firstWhere('id', (int) $id)?->name ?? "#{$id}";
    }

    private function activityCategoryLabel(mixed $id): string
    {
        if (! $id) {
            return 'Sin categoría';
        }

        return CatalogCacheService::categories()->firstWhere('id', (int) $id)?->name ?? "#{$id}";
    }

    private function activityGroupLabel(mixed $id): string
    {
        if (! $id) {
            return 'Sin equipo';
        }

        return CatalogCacheService::groups()->firstWhere('id', (int) $id)?->name ?? "#{$id}";
    }

    private function activityAssigneeLabel(mixed $id): string
    {
        if (! $id) {
            return 'Sin asignar';
        }

        $agent = CatalogCacheService::agents()->firstWhere('id', (int) $id);

        if ($agent) {
            return trim($agent->firstname.' '.$agent->lastname);
        }

        // CatalogCacheService::agents() solo incluye agentes con
        // available=true, así que un asignatario dado de baja o marcado como
        // no disponible no aparece ahí. Antes se caía a "Usuario #id", que
        // dejaba ilegible justo el historial de los tickets más antiguos
        // (que son los que más se consultan por auditoría). Se resuelve
        // contra la tabla de usuarios y se memoiza por petición: son ids
        // sueltos repetidos muchas veces dentro de un mismo historial, no un
        // listado, así que el coste real es una consulta por agente
        // desaparecido y por request.
        if (array_key_exists((int) $id, self::$assigneeLabelCache)) {
            return self::$assigneeLabelCache[(int) $id];
        }

        $user = User::select(['id', 'firstname', 'lastname', 'email'])->find((int) $id);
        $label = $user
            ? (trim($user->firstname.' '.$user->lastname) ?: (string) $user->email)
            : "Usuario #{$id}";

        return self::$assigneeLabelCache[(int) $id] = $label;
    }
}
