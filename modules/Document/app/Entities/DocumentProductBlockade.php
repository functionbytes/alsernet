<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentProductBlockade extends Model
{
    protected $table = 'document_product_blockades';

    protected $fillable = [
        'source_id',
        'product_id',
        'product_attribute_id',
        'document_type_id',
        'blockade_type',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'product_id' => 'integer',
            'product_attribute_id' => 'integer',
            'document_type_id' => 'integer',
            'blockade_type' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

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

    public static function requiresDni(?int $idProduct = null, ?int $idProductAttribute = null): bool
    {
        return static::hasBlockade($idProduct, $idProductAttribute, 'dni');
    }

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
