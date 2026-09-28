<?php

namespace Modules\HelpdeskAiPrompts\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Modules\HelpdeskAiPrompts\Models\AiPromptVersion;

/**
 * Every save (create or update) of a block/case snapshots itself into
 * helpdesk_ai_prompt_versions and bumps `version`. Dirty updates increment
 * the counter; a plain re-save (e.g. an idempotent seeder re-run) keeps the
 * same version number but still records a fresh snapshot.
 */
trait HasPromptVersions
{
    protected static function bootHasPromptVersions(): void
    {
        static::creating(function (self $model): void {
            // The migration defaults `version` to 1 at the DB level, but that
            // default is invisible to a freshly-built in-memory model — set it
            // explicitly so the very first version snapshot below is correct.
            if ($model->version === null) {
                $model->version = 1;
            }
        });

        static::updating(function (self $model): void {
            if ($model->isDirty() && ! $model->isDirty('version')) {
                $model->version = ((int) ($model->getOriginal('version') ?? 1)) + 1;
            }
        });

        static::saved(function (self $model): void {
            AiPromptVersion::query()->create([
                'subject_type' => $model->versionSubjectType(),
                'subject_id' => $model->getKey(),
                'version' => $model->version,
                'snapshot' => $model->attributesToArray(),
                'created_by' => Auth::id() ?? $model->updated_by,
                'created_at' => now(),
            ]);
        });
    }

    abstract public function versionSubjectType(): string;

    /**
     * @return HasMany<AiPromptVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AiPromptVersion::class, 'subject_id')
            ->where('subject_type', $this->versionSubjectType())
            ->orderByDesc('version')
            ->orderByDesc('id');
    }

    public function restoreVersion(AiPromptVersion $version): bool
    {
        if ($version->subject_type !== $this->versionSubjectType() || (int) $version->subject_id !== (int) $this->getKey()) {
            throw new \InvalidArgumentException('La versión no pertenece a este registro.');
        }

        $snapshot = collect($version->snapshot)
            ->except(['id', 'version', 'created_at', 'updated_at'])
            ->toArray();

        $this->fill($snapshot);

        return $this->save();
    }
}
