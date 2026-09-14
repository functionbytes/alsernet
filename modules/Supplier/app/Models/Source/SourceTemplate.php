<?php

namespace Modules\Supplier\Models\Source;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Source\SourceTemplateFactory;

class SourceTemplate extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_source_templates';

    protected $fillable = [
        'name',
        'description',
        'source_type',
        'connection_template',
        'extraction_template',
        'schedule_template',
        'retry_template',
        'validation_template',
        'required_variables',
        'category',
        'tags',
        'usage_count',
        'is_public',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'connection_template' => 'array',
            'extraction_template' => 'array',
            'schedule_template' => 'array',
            'retry_template' => 'array',
            'validation_template' => 'array',
            'required_variables' => 'array',
            'tags' => 'array',
            'usage_count' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    protected static function newFactory(): SourceTemplateFactory
    {
        return SourceTemplateFactory::new();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeBySourceType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function replaceVariables(array $variables): array
    {
        $templates = [
            'connection' => $this->connection_template,
            'extraction' => $this->extraction_template,
            'schedule' => $this->schedule_template,
            'retry' => $this->retry_template,
            'validation' => $this->validation_template,
        ];

        return array_map(function ($template) use ($variables) {
            return $this->replaceVariablesInTemplate($template, $variables);
        }, $templates);
    }

    protected function replaceVariablesInTemplate(mixed $template, array $variables): mixed
    {
        if (is_string($template)) {
            foreach ($variables as $key => $value) {
                $template = str_replace("{{{$key}}}", $value, $template);
            }

            return $template;
        }

        if (is_array($template)) {
            return array_map(fn ($item) => $this->replaceVariablesInTemplate($item, $variables), $template);
        }

        return $template;
    }

    public function getMissingVariables(array $providedVariables): array
    {
        return array_diff($this->required_variables ?? [], array_keys($providedVariables));
    }

    public function hasAllRequiredVariables(array $providedVariables): bool
    {
        return empty($this->getMissingVariables($providedVariables));
    }
}
