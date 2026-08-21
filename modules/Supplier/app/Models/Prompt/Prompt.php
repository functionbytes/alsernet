<?php

namespace Modules\Supplier\Models\Prompt;

use App\Models\User;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Supplier\Database\Factories\Prompt\PromptFactory;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Supplier\Supplier;

class Prompt extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_prompts';

    protected $fillable = [
        'uid',
        'supplier_id',
        'category_id',
        'subfamily_id',
        'subfamily_ids',
        'subfamily_mode',
        'source_id',
        'scope',
        'label',
        'prompt_template',
        'output_language',
        'tone',
        'priority',
        'seo_focus',
        'enable_web_search',
        'ai_model',
        'required_sections',
        'version',
        'is_default',
        'is_active',
        'is_template',
        'template_category',
        'cloned_from_template_id',
        'content_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'required_sections' => 'array',
            'seo_focus' => 'boolean',
            'enable_web_search' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'is_template' => 'boolean',
            'priority' => 'integer',
            'version' => 'integer',
            'supplier_id' => 'integer',
            'category_id' => 'integer',
            'subfamily_id' => 'integer',
            'subfamily_ids' => 'array',
            'source_id' => 'integer',
            'cloned_from_template_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $prompt) {
            if (is_null($prompt->uid)) {
                $prompt->uid = (string) Str::ulid();
            }
        });

        static::saved(fn () => self::flushResolveCache());
        static::deleted(fn () => self::flushResolveCache());
    }

    protected static function newFactory(): PromptFactory
    {
        return PromptFactory::new();
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'supplier_prompt_favorites', 'prompt_id', 'user_id')
            ->withTimestamps();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function subfamily(): BelongsTo
    {
        return $this->belongsTo(Subfamily::class, 'subfamily_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(AiContent::class, 'prompt_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PromptVersion::class, 'prompt_id')->orderByDesc('version');
    }

    /**
     * Template source - if this prompt was cloned from a template
     */
    public function templateSource(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'cloned_from_template_id');
    }

    /**
     * Prompts that were cloned from this template
     */
    public function clonedPrompts(): HasMany
    {
        return $this->hasMany(Prompt::class, 'cloned_from_template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefaults($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    public function scopeGlobal($query)
    {
        return $query->where('scope', 'global');
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeForSource($query, int $sourceId)
    {
        return $query->where('source_id', $sourceId);
    }

    public function scopeUid($query, string $uid)
    {
        return $query->where('uid', $uid);
    }

    /**
     * Filter only templates
     */
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    /**
     * Filter templates by category
     */
    public function scopeTemplateCategory($query, string $category)
    {
        return $query->templates()->where('template_category', $category);
    }

    /**
     * Filter non-template prompts
     */
    public function scopeNotTemplates($query)
    {
        return $query->where('is_template', false);
    }

    /**
     * Clone this template into a new prompt
     *
     * @param  array  $overrides  Fields to override (supplier_id, category_id, etc.)
     */
    public function cloneFromTemplate(array $overrides = []): self
    {
        // Prepare base data from template
        $data = [
            'label' => $this->label,
            'prompt_template' => $this->prompt_template,
            'scope' => $this->scope,
            'content_type' => $this->content_type ?? 'description',
            'output_language' => $this->output_language,
            'tone' => $this->tone,
            'seo_focus' => $this->seo_focus,
            'priority' => $this->priority,
            'version' => 1,
            'is_active' => true,
            'is_template' => false, // Cloned prompts are NOT templates
            'cloned_from_template_id' => $this->id,
            'required_sections' => $this->required_sections,
            'notes' => $this->notes ?? null,
        ];

        // Merge with overrides
        $data = array_merge($data, $overrides);

        // Generate new UID
        $data['uid'] = (string) Str::ulid();

        // Create new prompt
        return self::create($data);
    }

    /**
     * Resolve the best matching prompt based on priority system.
     *
     * Priority order (highest to lowest):
     * 1. Prompt specific to SOURCE + SUPPLIER + CATEGORY
     * 2. Prompt specific to SUPPLIER + CATEGORY
     * 3. Prompt specific to SOURCE + SUPPLIER
     * 4. Prompt specific to SUPPLIER
     * 5. Prompt specific to CATEGORY
     * 6. Global default prompt
     */
    public static function flushResolveCache(): void
    {
        Cache::tags(['prompt_resolve'])->flush();
    }

    public static function resolvePrompt(
        ?int $supplierId = null,
        ?int $categoryId = null,
        ?int $sourceId = null,
        ?int $subfamilyId = null
    ): ?self {
        $cacheKey = 'prompt_resolve:'.($supplierId ?? 0).':'.($categoryId ?? 0).':'.($sourceId ?? 0).':'.($subfamilyId ?? 0);

        $id = Cache::tags(['prompt_resolve'])->remember($cacheKey, 600, function () use ($supplierId, $categoryId, $sourceId, $subfamilyId) {
            return self::resolvePromptUncached($supplierId, $categoryId, $sourceId, $subfamilyId)?->id;
        });

        return $id ? self::find($id) : null;
    }

    private static function resolvePromptUncached(
        ?int $supplierId = null,
        ?int $categoryId = null,
        ?int $sourceId = null,
        ?int $subfamilyId = null
    ): ?self {
        $query = self::query()->active()->byPriority();

        // 1. Proveedor + subfamilia + fuente
        if ($supplierId && $subfamilyId && $sourceId) {
            $prompt = (clone $query)
                ->where('supplier_id', $supplierId)
                ->where('subfamily_id', $subfamilyId)
                ->where('source_id', $sourceId)
                ->where('scope', 'source')
                ->first();
            if ($prompt) {
                return $prompt;
            }
        }

        // 2. Proveedor + subfamilia
        if ($supplierId && $subfamilyId) {
            $prompt = (clone $query)
                ->where('supplier_id', $supplierId)
                ->where('subfamily_id', $subfamilyId)
                ->whereIn('scope', ['supplier_category', 'category'])
                ->first();
            if ($prompt) {
                return $prompt;
            }
        }

        // 3. Proveedor + familia + fuente
        if ($supplierId && $categoryId && $sourceId) {
            $prompt = (clone $query)
                ->where('supplier_id', $supplierId)
                ->where('category_id', $categoryId)
                ->where('source_id', $sourceId)
                ->where('scope', 'source')
                ->first();
            if ($prompt) {
                return $prompt;
            }
        }

        // 4. Proveedor + familia
        if ($supplierId && $categoryId) {
            $prompt = (clone $query)
                ->where('supplier_id', $supplierId)
                ->where('category_id', $categoryId)
                ->where('scope', 'supplier_category')
                ->first();
            if ($prompt) {
                return $prompt;
            }
        }

        // 5. Proveedor + fuente
        if ($supplierId && $sourceId) {
            $prompt = (clone $query)
                ->where('supplier_id', $supplierId)
                ->where('source_id', $sourceId)
                ->where('scope', 'source')
                ->first();
            if ($prompt) {
                return $prompt;
            }
        }

        // 6. Proveedor
        if ($supplierId) {
            $prompt = (clone $query)
                ->where('supplier_id', $supplierId)
                ->where('scope', 'supplier')
                ->first();
            if ($prompt) {
                return $prompt;
            }
        }

        // 7. Subfamilia
        if ($subfamilyId) {
            $prompt = (clone $query)
                ->where('subfamily_id', $subfamilyId)
                ->where('scope', 'category')
                ->first();
            if ($prompt) {
                return $prompt;
            }
        }

        // 8. Familia
        if ($categoryId) {
            $prompt = (clone $query)
                ->where('category_id', $categoryId)
                ->where('scope', 'category')
                ->first();
            if ($prompt) {
                return $prompt;
            }
        }

        // 9. Global default
        return (clone $query)
            ->where('scope', 'global')
            ->where('is_default', true)
            ->first();
    }

    /**
     * Max length of a single substituted value. ERP-sourced product attributes
     * can be arbitrarily long; capping prevents prompt-bloat blowing through
     * the model context window and curbs cost.
     */
    private const VARIABLE_VALUE_MAX = 2000;

    public function render(array $variables = []): string
    {
        // Single-pass substitution via strtr() so a value that happens to
        // contain another variable's placeholder (e.g. an ERP description that
        // includes "{{ system_instructions }}") is NOT re-processed in a
        // subsequent loop iteration. The previous iterative str_replace
        // implementation was vulnerable to prompt injection through any
        // user-controlled / ERP-sourced attribute.
        $replacements = [];
        foreach ($variables as $key => $value) {
            // Convertir arrays a string de forma segura (evita Array to string conversion)
            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $value)));
            }
            $value = (string) $value;
            if (mb_strlen($value) > self::VARIABLE_VALUE_MAX) {
                $value = mb_substr($value, 0, self::VARIABLE_VALUE_MAX).'…';
            }
            $replacements['{{ '.$key.' }}'] = $value;
            $replacements['{{'.$key.'}}'] = $value;
        }

        $rendered = strtr($this->prompt_template, $replacements);

        // Cualquier variable que la plantilla use pero el caller no haya
        // proporcionado queda como '{{nombre}}' literal tras el strtr — el
        // modelo lo lee como un placeholder sin rellenar y se niega a
        // redactar ("son marcadores de posición, deme datos reales"). Se
        // vacía en vez de dejarlo literal; una sección vacía es mucho menos
        // confuso para el modelo que sintaxis de plantilla cruda.
        return preg_replace('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', '', $rendered);
    }

    public function createNewVersion(): self
    {
        $newPrompt = $this->replicate();
        $newPrompt->version = $this->version + 1;
        $newPrompt->uid = (string) Str::ulid();
        $newPrompt->save();

        return $newPrompt;
    }
}
