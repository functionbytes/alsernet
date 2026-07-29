<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBlockade extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'product_blockades';

    /**
     * Blockade type constants (legacy compatibility)
     * Note: These are kept for backward compatibility but document_type_id is now preferred
     */
    public const TYPE_DNI = 'dni';

    public const TYPE_ESCOPETA = 'escopeta';

    public const TYPE_RIFLE = 'rifle';

    public const TYPE_CORTA = 'corta';

    public const TYPE_BALINES = 'balines';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'source_id',
        'product_id',
        'product_attribute_id',
        'document_type_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'product_id' => 'integer',
            'product_attribute_id' => 'integer',
            'document_type_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the document type for this blockade
     * Uses dynamic model reference to avoid hard dependency on Document module
     */
    public function documentType(): BelongsTo
    {
        $modelClass = 'Modules\Document\Entities\DocumentType';

        return $this->belongsTo($modelClass, 'document_type_id');
    }

    /**
     * Check if product has a specific blockade type
     * Works with both simple products (product_id) and combinations (product_attribute_id)
     *
     * @param  int|null  $idProduct  Product ID
     * @param  int|null  $idProductAttribute  Product attribute ID (combination)
     * @param  string|null  $documentTypeSlug  Document type slug (e.g., 'dni', 'rifle')
     */
    public static function hasBlockade(?int $idProduct = null, ?int $idProductAttribute = null, ?string $documentTypeSlug = null): bool
    {
        $query = static::query();

        if ($idProduct !== null) {
            $query->where('product_id', $idProduct);
        }

        if ($idProductAttribute !== null) {
            $query->where('product_attribute_id', $idProductAttribute);
        }

        if ($documentTypeSlug !== null) {
            $query->whereHas('documentType', function ($q) use ($documentTypeSlug) {
                $q->where('slug', $documentTypeSlug);
            });
        }

        return $query->exists();
    }

    /**
     * Get all blockade types (slugs) for a product (simple or combination)
     */
    public static function getBlockadeTypes(?int $idProduct = null, ?int $idProductAttribute = null): array
    {
        $query = static::query()->with('documentType');

        if ($idProduct !== null) {
            $query->where('product_id', $idProduct);
        }

        if ($idProductAttribute !== null) {
            $query->where('product_attribute_id', $idProductAttribute);
        }

        return $query->get()
            ->pluck('documentType.slug')
            ->filter()
            ->toArray();
    }

    /**
     * Check if product has DNI requirement
     */
    public static function requiresDni(?int $idProduct = null, ?int $idProductAttribute = null): bool
    {
        return static::hasBlockade($idProduct, $idProductAttribute, 'dni');
    }

    /**
     * Get sale type for a product (returns first found document type slug)
     */
    public static function getSaleType(?int $idProduct = null, ?int $idProductAttribute = null): ?string
    {
        $query = static::query()->with('documentType');

        if ($idProduct !== null) {
            $query->where('product_id', $idProduct);
        }

        if ($idProductAttribute !== null) {
            $query->where('product_attribute_id', $idProductAttribute);
        }

        $blockade = $query->first();

        return $blockade?->documentType?->slug;
    }
}
