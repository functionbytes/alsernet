<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTemplate extends Model
{
    protected $table = 'seo_templates';

    protected $fillable = [
        'name',
        'model_type',
        'title_pattern',
        'description_pattern',
        'og_type',
        'twitter_card',
        'robots',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /**
     * Scope to filter active templates.
     */
    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter templates for a specific model type (or global).
     */
    public function scopeForModel($query, string $modelType): mixed
    {
        return $query->where(fn ($q) => $q->where('model_type', $modelType)->orWhereNull('model_type'));
    }

    /**
     * Apply template patterns to a model, replacing {variables}.
     *
     * @return array<string, string>
     */
    public function applyToModel(Model $model): array
    {
        $vars = [
            '{title}' => $model->title ?? $model->name ?? '',
            '{name}' => $model->name ?? $model->title ?? '',
            '{description}' => strip_tags($model->description ?? $model->excerpt ?? ''),
            '{excerpt}' => strip_tags($model->excerpt ?? $model->description ?? ''),
            '{site_name}' => config('app.name', ''),
            '{site_url}' => config('app.url', ''),
            '{year}' => now()->year,
            '{month}' => now()->format('F'),
        ];

        $result = [];

        if ($this->title_pattern) {
            $title = str_replace(array_keys($vars), array_values($vars), $this->title_pattern);
            $result['title'] = mb_substr(trim($title), 0, 60);
        }

        if ($this->description_pattern) {
            $desc = str_replace(array_keys($vars), array_values($vars), $this->description_pattern);
            $desc = preg_replace('/\s+/', ' ', strip_tags($desc));
            $result['description'] = mb_substr(trim($desc ?? ''), 0, 160);
        }

        if ($this->og_type) {
            $result['og_type'] = $this->og_type;
        }

        if ($this->twitter_card) {
            $result['twitter_card'] = $this->twitter_card;
        }

        if ($this->robots) {
            $result['robots'] = $this->robots;
        }

        return $result;
    }
}
