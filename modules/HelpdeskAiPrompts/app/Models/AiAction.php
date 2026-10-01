<?php

namespace Modules\HelpdeskAiPrompts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Modules\HelpdeskAiPrompts\Models\Concerns\HasPromptVersions;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionDefinitionValidator;

class AiAction extends Model
{
    use HasPromptVersions;

    public const TYPE_BUILTIN = 'builtin';

    public const TYPE_BRIDGE = 'bridge';

    public const TYPE_HTTP = 'http';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_actions';

    protected $fillable = [
        'key',
        'name',
        'description',
        'type',
        'is_active',
        'parameters',
        'config',
        'response',
        'rules',
        'secrets',
        'channels',
        'version',
        'updated_by',
    ];

    protected $hidden = ['secrets'];

    protected static function booted(): void
    {
        // Una definición inválida (acción del bridge fuera de la lista blanca,
        // host http no permitido, write sin confirmación...) nunca se guarda.
        static::saving(function (self $action): void {
            app(ActionDefinitionValidator::class)->assertValid($action);
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parameters' => 'array',
            'config' => 'array',
            'response' => 'array',
            'rules' => 'array',
            'channels' => 'array',
            'secrets' => 'encrypted:array',
            'version' => 'integer',
        ];
    }

    public function versionSubjectType(): string
    {
        return 'action';
    }

    /**
     * Los secretos jamás entran en el historial de versiones.
     *
     * @return array<string, mixed>
     */
    public function versionSnapshot(): array
    {
        return Arr::except($this->attributesToArray(), ['secrets']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
