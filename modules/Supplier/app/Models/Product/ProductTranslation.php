<?php

namespace Modules\Supplier\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\Lang;
use Modules\Supplier\Database\Factories\Product\ProductTranslationFactory;

/**
 * ProductTranslation Model
 *
 * Stores AI-generated product descriptions in multiple languages
 *
 * @property int $id
 * @property int $product_id
 * @property int $lang_id Foreign key to langs table
 * @property string $description Translated product description
 * @property bool $generated_by_ai Whether this was AI-generated
 * @property string|null $prompt_used The AI prompt template used
 * @property string|null $ai_model The AI model used (gpt-4, claude-3, etc.)
 */
class ProductTranslation extends Model
{
    use HasFactory;

    protected $table = 'supplier_product_translations';

    protected $fillable = [
        'product_id',
        'lang_id',
        'description',
        'generated_by_ai',
        'prompt_used',
        'ai_model',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'lang_id' => 'integer',
        'generated_by_ai' => 'boolean',
    ];

    protected static function newFactory(): ProductTranslationFactory
    {
        return ProductTranslationFactory::new();
    }

    /**
     * Get the product that owns this translation
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the language for this translation
     */
    public function lang(): BelongsTo
    {
        return $this->belongsTo(Lang::class, 'lang_id');
    }

    /**
     * Scope: Filter by language ID
     */
    public function scopeForLang($query, int $langId)
    {
        return $query->where('lang_id', $langId);
    }

    /**
     * Scope: Filter by ISO code (via Lang relationship)
     */
    public function scopeForLocale($query, string $isoCode)
    {
        return $query->whereHas('lang', function ($q) use ($isoCode) {
            $q->where('iso_code', $isoCode);
        });
    }

    /**
     * Scope: Only AI-generated translations
     */
    public function scopeAiGenerated($query)
    {
        return $query->where('generated_by_ai', true);
    }

    /**
     * Scope: Only manual translations
     */
    public function scopeManual($query)
    {
        return $query->where('generated_by_ai', false);
    }

    /**
     * Scope: By AI model
     */
    public function scopeByAiModel($query, string $model)
    {
        return $query->where('ai_model', $model);
    }
}
