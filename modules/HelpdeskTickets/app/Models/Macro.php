<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public static array $actionTypes = [
        'reply' => 'Enviar respuesta',
        'internal_note' => 'Añadir nota interna',
        'assign_group' => 'Asignar grupo',
        'assign_user' => 'Asignar agente',
        'set_priority' => 'Cambiar prioridad',
        'set_status' => 'Cambiar estado',
        'add_tag' => 'Añadir etiqueta',
        'close' => 'Cerrar ticket',
    ];

    public function casts(): array
    {
        return [
            'actions' => 'array',
            'is_shared' => 'boolean',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
