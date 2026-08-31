<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskTickets\Models\Concerns\BelongsToHelpdeskUser;

class Macro extends Model
{
    use BelongsToHelpdeskUser, HasFactory, SoftDeletes;

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

    /**
     * Que necesita cada accion ademas del `type`, y como se escribe. Fuente
     * unica: la usan la ayuda del formulario y ValidMacroActions, para que lo
     * que se documenta y lo que se acepta no se separen.
     *
     * @return array<string, array{key: string|null, hint: string}>
     */
    public static function actionSpecs(): array
    {
        return [
            'reply' => ['key' => 'body', 'hint' => 'texto de la respuesta al cliente'],
            'internal_note' => ['key' => 'body', 'hint' => 'texto de la nota, solo visible para el equipo'],
            'assign_group' => ['key' => 'value', 'hint' => 'id del grupo'],
            'assign_user' => ['key' => 'value', 'hint' => 'id del agente'],
            'set_priority' => ['key' => 'value', 'hint' => 'slug de la prioridad, p. ej. "alta"'],
            'set_status' => ['key' => 'value', 'hint' => 'id del estado'],
            'add_tag' => ['key' => 'value', 'hint' => 'etiqueta a añadir'],
            'close' => ['key' => null, 'hint' => 'no necesita nada mas'],
        ];
    }

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
        return $this->belongsToHelpdeskUser('user_id', 'user');
    }
}
