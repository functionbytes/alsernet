<?php

namespace Modules\HelpdeskAiPrompts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HelpdeskAiPrompts\Models\Concerns\HasPromptVersions;

class AiPromptBlock extends Model
{
    use HasFactory, HasPromptVersions;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_prompt_blocks';

    protected $fillable = [
        'key',
        'kind',
        'name',
        'content',
        'channel',
        'locale',
        'is_active',
        'version',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function versionSubjectType(): string
    {
        return 'block';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Bloques globales (channel null) o del canal indicado.
     */
    public function scopeForChannel(Builder $query, ?string $channel): Builder
    {
        return $query->where(function (Builder $q) use ($channel) {
            $q->whereNull('channel');

            if ($channel !== null) {
                $q->orWhere('channel', $channel);
            }
        });
    }
}
