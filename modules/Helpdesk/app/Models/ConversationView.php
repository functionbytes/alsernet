<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Database\Factories\ConversationViewFactory;

class ConversationView extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_views';

    /** @var array<int, string>|null */
    private static ?array $statusNames = null;

    /** @var array<int, string>|null */
    private static ?array $groupNames = null;

    protected $fillable = [
        'name',
        'description',
        'filters',
        'sort_by',
        'sort_direction',
        'user_id',
        'is_public',
        'is_default',
        'is_system',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_public' => 'boolean',
            'is_default' => 'boolean',
            'is_system' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-increment order for new views
        static::creating(function ($view) {
            if (is_null($view->order)) {
                $maxOrder = static::where('user_id', $view->user_id)->max('order') ?? 0;
                $view->order = $maxOrder + 1;
            }

            // Ensure only one default view per user
            if ($view->is_default && $view->user_id) {
                static::where('user_id', $view->user_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($view) {
            // Ensure only one default view per user
            if ($view->is_default && $view->isDirty('is_default') && $view->user_id) {
                static::where('id', '!=', $view->id)
                    ->where('user_id', $view->user_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Get the user that owns the view.
     * Note: User model is in the default connection, not helpdesk
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get only public views.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get views for a specific user (owned + public).
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere('is_public', true);
        });
    }

    /**
     * Scope to get views ordered by their sort order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

    /**
     * Check if this view can be deleted.
     */
    public function canDelete(): bool
    {
        return ! $this->is_system;
    }

    /**
     * Check if this view can be edited by a user.
     */
    public function canEdit(int $userId): bool
    {
        return ! $this->is_system && ($this->user_id === $userId || is_null($this->user_id));
    }

    protected static function newFactory(): ConversationViewFactory
    {
        return new ConversationViewFactory;
    }

    /**
     * Los criterios de ordenación admitidos, con su nombre en castellano.
     *
     * Vive aquí y no en cada Blade porque el listado y los dos formularios
     * pintaban la misma lista por su cuenta. Las claves son exactamente las que
     * valida StoreConversationViewRequest.
     *
     * @return array<string, string>
     */
    public static function sortLabels(): array
    {
        return [
            'created_at' => 'Fecha de creación',
            'updated_at' => 'Última actualización',
            'priority' => 'Prioridad',
            'status' => 'Estado',
            'assignee_id' => 'Agente asignado',
        ];
    }

    /**
     * Resumen legible de los filtros, para el listado de vistas.
     */
    public function getFilterSummary(): string
    {
        $labels = $this->filterLabels();

        return $labels === [] ? 'Sin filtros' : implode(', ', $labels);
    }

    /**
     * Los filtros traducidos a etiquetas en castellano, una por filtro activo.
     *
     * Descarta exactamente lo mismo que ConversationFilter::isBlank(), no lo
     * que descarta empty(): en este vocabulario false es significativo
     * (is_open=false es la vista "solo cerradas"), así que con empty() una
     * vista perfectamente filtrada se anunciaba como "Sin filtros".
     *
     * @return array<int, string>
     */
    public function filterLabels(): array
    {
        $labels = [];

        foreach ($this->filters ?? [] as $key => $value) {
            if ($value === null || $value === '' || $value === 'all' || $value === []) {
                continue;
            }

            $label = $this->labelFor((string) $key, $value);

            if ($label !== null) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    /**
     * @return string|null null = el filtro no pinta nada en el resumen
     */
    private function labelFor(string $key, mixed $value): ?string
    {
        $isTrue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return match ($key) {
            'is_open' => $isTrue ? 'Abiertas' : 'Cerradas',
            'is_archived', 'archived' => $isTrue ? 'Archivadas' : 'Sin archivar',
            'snoozed' => $isTrue ? 'Pospuestas' : null,
            'unread' => $isTrue ? 'Sin leer' : null,
            'mine' => $isTrue ? 'Asignadas a mí' : null,
            'vip' => $isTrue ? 'Clientes VIP' : null,
            'urgent' => $isTrue ? 'Urgentes' : null,
            'status_id' => 'Estado: '.(static::statusNames()[(int) $value] ?? "#{$value}"),
            'status' => 'Estado: '.$this->readableStatus((string) $value),
            'assignee' => $this->readableAssignee($value),
            'group' => 'Grupo: '.(static::groupNames()[(int) $value] ?? "#{$value}"),
            'priority' => 'Prioridad: '.$this->readablePriority((string) $value),
            'channel' => 'Canal: '.$this->readableChannel((string) $value),
            'inbox' => "Buzón #{$value}",
            'tag' => "Etiqueta #{$value}",
            'search' => 'Busca «'.$value.'»',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }

    private function readableAssignee(mixed $value): string
    {
        return match ((string) $value) {
            'unassigned' => 'Sin asignar',
            'mine' => 'Asignadas a mí',
            default => "Agente #{$value}",
        };
    }

    private function readableStatus(string $value): string
    {
        if (is_numeric($value)) {
            return static::statusNames()[(int) $value] ?? "#{$value}";
        }

        return match ($value) {
            'closed' => 'cerradas',
            'pending' => 'Esperando',
            'snoozed' => 'pospuestas',
            default => $value,
        };
    }

    private function readablePriority(string $value): string
    {
        return match ($value) {
            'urgent' => 'urgente',
            'high' => 'alta',
            'normal' => 'normal',
            'low' => 'baja',
            default => $value,
        };
    }

    private function readableChannel(string $value): string
    {
        return match ($value) {
            'whatsapp' => 'WhatsApp',
            'email' => 'Correo',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'web' => 'Web',
            default => ucfirst($value),
        };
    }

    /**
     * Nombres de estado y de grupo, cacheados por petición: el listado pinta
     * una etiqueta por vista y cada una miraría lo mismo (N+1 tonto).
     *
     * @return array<int, string>
     */
    private static function statusNames(): array
    {
        return static::$statusNames ??= ConversationStatus::pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    private static function groupNames(): array
    {
        return static::$groupNames ??= Group::pluck('name', 'id')->all();
    }

    /**
     * Reorder views based on an array of IDs.
     */
    public static function reorder(array $ids, ?int $userId = null): void
    {
        foreach ($ids as $order => $id) {
            $query = static::where('id', $id);
            if ($userId) {
                $query->where('user_id', $userId);
            }
            $query->update(['order' => $order + 1]);
        }
    }
}
