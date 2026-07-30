<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Database\Factories\MacroFactory;

class Macro extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_macros';

    protected $fillable = [
        'name',
        'description',
        'actions',
        'is_shared',
        'user_id',
        'usage_count',
        'last_used_at',
        'is_active',
    ];

    public const ACTION_TYPES = [
        'assign_agent' => 'Asignar agente',
        'assign_group' => 'Asignar grupo',
        'add_tag' => 'Agregar etiqueta',
        'remove_tag' => 'Quitar etiqueta',
        'change_status' => 'Cambiar estado',
        'change_priority' => 'Cambiar prioridad',
        'add_note' => 'Agregar nota interna',
        'send_reply' => 'Enviar respuesta',
        'resolve_conversation' => 'Resolver conversacion',
        'close_conversation' => 'Cerrar conversacion',
    ];

    protected function casts(): array
    {
        return [
            'actions' => 'array',
            'is_shared' => 'boolean',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->where('is_shared', true);
    }

    public function scopePersonal(Builder $query, int $userId): Builder
    {
        return $query->where('is_shared', false)->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    protected static function newFactory(): MacroFactory
    {
        return new MacroFactory;
    }
}
