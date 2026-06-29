<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Document\Traits\HasUid;

class DocumentType extends Model
{
    use HasUid;

    protected $table = 'document_types';

    protected $fillable = [
        'uid',
        'slug',
        'label',
        'description',
        'icon',
        'color',
        'is_active',
        'sort_order',
        'sla_multiplier',
        'validation_stages',
        'associated_labels',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'sla_multiplier' => 'decimal:2',
            'validation_stages' => 'array',
            'associated_labels' => 'array',
        ];
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class)->orderBy('sort_order');
    }

    public function validationStages(): HasMany
    {
        return $this->hasMany(DocumentTypeValidationStage::class)->orderBy('order');
    }

    public function getTranslationsList()
    {
        $langs = DocumentLang::all();
        $translations = [];

        foreach ($langs as $lang) {
            $locale = $lang->locale ?? $lang->code;
            $translations[] = (object) [
                'id' => null,
                'lang_id' => $lang->id,
                'label' => trans("documents.types.{$this->slug}.label", [], $locale),
                'description' => trans("documents.types.{$this->slug}.description", [], $locale),
                'instructions' => trans("documents.types.{$this->slug}.instructions", [], $locale),
            ];
        }

        return collect($translations);
    }

    // Get translation for specific language
    public function translate(?int $langId = null)
    {
        $langId = $langId ?? DocumentLang::getDefaultLangId();

        return $this->getTranslationsList()->firstWhere('lang_id', $langId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function getLabel(): string
    {
        return __("documents.types.{$this->slug}.label");
    }

    public function getDescription(): string
    {
        return __("documents.types.{$this->slug}.description");
    }

    public function getInstructions(): string
    {
        return __("documents.types.{$this->slug}.instructions");
    }

    public function getLabelAttribute(): string
    {
        $value = $this->attributes['label'] ?? null;

        return $value ?: $this->getLabel();
    }

    public function getDescriptionAttribute(): string
    {
        $value = $this->attributes['description'] ?? null;

        return $value ?: $this->getDescription();
    }

    public function getInstructionsAttribute(): string
    {
        return $this->getInstructions();
    }

    // Static method to get by slug
    public static function getBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)
            ->with('requirements')
            ->first();
    }

    public function getRequiredDocuments(?int $langId = null): array
    {
        $docs = [];

        foreach ($this->requirements as $requirement) {
            $docs[$requirement->key] = $requirement->getName($langId);
        }

        return $docs;
    }

    public function getValidationStages(): array
    {
        // Get from relational table first (new format)
        $stages = $this->validationStages()
            ->active()
            ->ordered()
            ->pluck('key')
            ->toArray();

        // Fallback for legacy data
        if (empty($stages)) {
            if (empty($this->validation_stages) || ! is_array($this->validation_stages)) {
                return ['documentacion']; // Default fallback
            }

            // Check if it's the old JSON format
            if (isset($this->validation_stages[0]) && is_array($this->validation_stages[0]) && isset($this->validation_stages[0]['key'])) {
                // New JSON format: sort by order and extract keys
                $stages = collect($this->validation_stages)
                    ->sortBy('order')
                    ->pluck('key')
                    ->values()
                    ->toArray();
            } else {
                // Old format: simple array of keys
                $stages = $this->validation_stages;
            }
        }

        return $stages;
    }

    public function getValidationStagesWithConditions(): array
    {
        // Get from relational table first (new format)
        $stages = $this->validationStages()
            ->active()
            ->ordered()
            ->get()
            ->map(function ($stage) {
                return [
                    'key' => $stage->key,
                    'order' => $stage->order,
                    'conditions' => $stage->conditions ?? [],
                ];
            })
            ->toArray();

        // Fallback for legacy data
        if (empty($stages)) {
            if (empty($this->validation_stages) || ! is_array($this->validation_stages)) {
                return [
                    [
                        'key' => 'documentacion',
                        'order' => 1,
                        'conditions' => [],
                    ],
                ];
            }

            // Check if it's the old JSON format
            if (isset($this->validation_stages[0]) && is_array($this->validation_stages[0]) && isset($this->validation_stages[0]['key'])) {
                // JSON format: sort by order
                $stages = collect($this->validation_stages)
                    ->sortBy('order')
                    ->values()
                    ->toArray();
            } else {
                // Old format: convert to new format
                $stages = collect($this->validation_stages)
                    ->map(function ($key, $index) {
                        return [
                            'key' => $key,
                            'order' => $index + 1,
                            'conditions' => [],
                        ];
                    })
                    ->values()
                    ->toArray();
            }
        }

        return $stages;
    }

    public static function getByType(string $type): ?self
    {
        return self::getBySlug($type);
    }

    public static function getValidBlockadeTypes(): array
    {
        return self::active()
            ->orderBy('slug')
            ->pluck('slug')
            ->toArray();
    }
}
