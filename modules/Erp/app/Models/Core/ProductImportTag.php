<?php

namespace Modules\Erp\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductImportTag extends Model
{
    protected $fillable = [
        'name',
        'description',
        'products_count',
    ];

    protected $casts = [
        'products_count' => 'integer',
    ];

    /**
     * Relación con ProductImports
     */
    public function productImports(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductImport::class,
            'product_import_tag',
            'tag_id',
            'product_import_id'
        )->withTimestamps();
    }

    /**
     * Relación con features de Prestashop
     */
    public function features(): HasMany
    {
        return $this->hasMany(ProductImportTagFeature::class, 'tag_id');
    }

    /**
     * Incrementar contador de productos
     */
    public function incrementProductsCount(): void
    {
        $this->increment('products_count');
    }

    /**
     * Decrementar contador de productos
     */
    public function decrementProductsCount(): void
    {
        $this->decrement('products_count');
    }

    /**
     * Recalcular contador de productos
     */
    public function recalculateProductsCount(): void
    {
        $this->products_count = $this->productImports()->count();
        $this->save();
    }

    /**
     * Obtener o crear tag por nombre
     */
    public static function findOrCreateByName(string $name, ?string $description = null): self
    {
        return self::firstOrCreate(
            ['name' => trim($name)],
            ['description' => $description]
        );
    }

    /**
     * Parsear etiquetas desde string separado por comas
     */
    public static function parseFromString(string $etiqueta): array
    {
        $tags = [];
        $parts = array_filter(array_map('trim', explode(',', $etiqueta)));

        foreach ($parts as $tagName) {
            if (! empty($tagName)) {
                $tags[] = self::findOrCreateByName($tagName);
            }
        }

        return $tags;
    }

    /**
     * Scopes
     */
    public function scopePopular($query, int $minCount = 5)
    {
        return $query->where('products_count', '>=', $minCount)
            ->orderBy('products_count', 'desc');
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }
}
