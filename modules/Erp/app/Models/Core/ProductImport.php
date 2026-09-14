<?php

namespace Modules\Erp\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Prestashop\Entities\Combination;
use Modules\Prestashop\Entities\Product;

class ProductImport extends Model
{
    protected $fillable = [
        'importable_type',
        'importable_id',
        'id_origen',
        'id_articulo',
        'unidades_oferta',
        'etiqueta',
        'estado_gestion',
        'activo',
        'es_segunda_mano',
        'externo_disponibilidad',
        'codigo_proveedor',
        'precio_costo_proveedor',
        'tarifa_proveedor',
        'es_arma',
        'es_arma_fogueo',
        'es_cartucho',
        'ean',
        'upc',
        'categoria',
        'familia',
        'subfamilia',
        'grupo',
        'last_sync_at',
        'sync_pending',
        'sync_errors',
    ];

    protected $casts = [
        'id_origen' => 'integer',
        'id_articulo' => 'integer',
        'unidades_oferta' => 'integer',
        'estado_gestion' => 'integer',
        'activo' => 'integer',
        'es_segunda_mano' => 'integer',
        'externo_disponibilidad' => 'integer',
        'precio_costo_proveedor' => 'decimal:2',
        'tarifa_proveedor' => 'decimal:2',
        'es_arma' => 'integer',
        'es_arma_fogueo' => 'integer',
        'es_cartucho' => 'integer',
        'categoria' => 'integer',
        'familia' => 'integer',
        'subfamilia' => 'integer',
        'grupo' => 'integer',
        'last_sync_at' => 'datetime',
        'sync_pending' => 'boolean',
        'sync_errors' => 'array',
    ];

    /**
     * Relación polimorfica - puede ser Product o Combination
     */
    public function importable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relación con tags
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductImportTag::class,
            'product_import_tag',
            'product_import_id',
            'tag_id'
        )->withTimestamps();
    }

    /**
     * Verificar si es un producto simple (sin combinaciones)
     */
    public function isSimpleProduct(): bool
    {
        return $this->importable_type === Product::class;
    }

    /**
     * Verificar si es una combinación
     */
    public function isCombination(): bool
    {
        return $this->importable_type === Combination::class;
    }

    /**
     * Obtener el producto relacionado (directo o a través de combinación)
     */
    public function getProduct(): ?Product
    {
        if ($this->isSimpleProduct()) {
            return $this->importable;
        }

        if ($this->isCombination() && $this->importable) {
            return $this->importable->product;
        }

        return null;
    }

    /**
     * Marcar como pendiente de sincronización
     */
    public function markAsSyncPending(?array $errors = null): bool
    {
        $this->sync_pending = true;
        if ($errors) {
            $this->sync_errors = $errors;
        }

        return $this->save();
    }

    /**
     * Marcar como sincronizado
     */
    public function markAsSynced(): bool
    {
        $this->sync_pending = false;
        $this->sync_errors = null;
        $this->last_sync_at = now();

        return $this->save();
    }

    /**
     * Sincronizar con Prestashop
     */
    public function syncToPrestashop(): bool
    {
        try {
            if ($this->isSimpleProduct()) {
                return $this->syncSimpleProduct();
            }

            if ($this->isCombination()) {
                return $this->syncCombination();
            }

            return false;

        } catch (\Exception $e) {
            $this->markAsSyncPending([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Sincronizar producto simple a aalv_combinacionunica_import
     */
    protected function syncSimpleProduct(): bool
    {
        \DB::connection('prestashop')->table('aalv_combinacionunica_import')->updateOrInsert(
            ['id_product' => $this->importable_id],
            [
                'id_origen' => $this->id_origen,
                'id_articulo' => $this->id_articulo,
                'unidades_oferta' => $this->unidades_oferta,
                'etiqueta' => $this->etiqueta,
                'estado_gestion' => $this->estado_gestion,
                'activo' => $this->activo,
                'es_segunda_mano' => $this->es_segunda_mano,
                'externo_disponibilidad' => $this->externo_disponibilidad,
                'codigo_proveedor' => $this->codigo_proveedor,
                'precio_costo_proveedor' => $this->precio_costo_proveedor,
                'tarifa_proveedor' => $this->tarifa_proveedor,
                'es_arma' => $this->es_arma,
                'es_arma_fogueo' => $this->es_arma_fogueo,
                'es_cartucho' => $this->es_cartucho,
                'ean' => $this->ean,
                'upc' => $this->upc,
                'categoria' => $this->categoria,
                'familia' => $this->familia,
                'subfamilia' => $this->subfamilia,
                'grupo' => $this->grupo,
            ]
        );

        return $this->markAsSynced();
    }

    /**
     * Sincronizar combinación a aalv_combinaciones_import
     */
    protected function syncCombination(): bool
    {
        \DB::connection('prestashop')->table('aalv_combinaciones_import')->updateOrInsert(
            ['id_product_attribute' => $this->importable_id],
            [
                'id_origen' => $this->id_origen,
                'id_articulo' => $this->id_articulo,
                'unidades_oferta' => $this->unidades_oferta,
                'etiqueta' => $this->etiqueta,
                'estado_gestion' => $this->estado_gestion,
                'activo' => $this->activo,
                'es_segunda_mano' => $this->es_segunda_mano,
                'externo_disponibilidad' => $this->externo_disponibilidad,
                'codigo_proveedor' => $this->codigo_proveedor,
                'precio_costo_proveedor' => $this->precio_costo_proveedor,
                'tarifa_proveedor' => $this->tarifa_proveedor,
                'es_arma' => $this->es_arma,
                'es_arma_fogueo' => $this->es_arma_fogueo,
                'es_cartucho' => $this->es_cartucho,
                'ean' => $this->ean,
                'upc' => $this->upc,
                'categoria' => $this->categoria,
                'familia' => $this->familia,
                'subfamilia' => $this->subfamilia,
                'grupo' => $this->grupo,
            ]
        );

        return $this->markAsSynced();
    }

    /**
     * Crear desde producto simple de Prestashop
     */
    public static function createFromProduct(Product $product, array $additionalData = []): self
    {
        return self::create(array_merge([
            'importable_type' => Product::class,
            'importable_id' => $product->id_product,
        ], $additionalData));
    }

    /**
     * Crear desde combinación de Prestashop
     */
    public static function createFromCombination(Combination $combination, array $additionalData = []): self
    {
        return self::create(array_merge([
            'importable_type' => Combination::class,
            'importable_id' => $combination->id_product_attribute,
        ], $additionalData));
    }

    /**
     * Procesar y sincronizar etiquetas desde el campo etiqueta
     */
    public function syncTagsFromEtiqueta(): void
    {
        if (empty($this->etiqueta)) {
            return;
        }

        // Parsear etiquetas desde el string
        $tags = ProductImportTag::parseFromString($this->etiqueta);

        // Obtener IDs de tags actuales
        $currentTagIds = $this->tags()->pluck('product_import_tags.id')->toArray();

        // Obtener IDs de nuevos tags
        $newTagIds = collect($tags)->pluck('id')->toArray();

        // Tags a eliminar (decrementar contador)
        $tagsToRemove = array_diff($currentTagIds, $newTagIds);
        foreach ($tagsToRemove as $tagId) {
            $tag = ProductImportTag::find($tagId);
            if ($tag) {
                $tag->decrementProductsCount();
            }
        }

        // Tags a agregar (incrementar contador)
        $tagsToAdd = array_diff($newTagIds, $currentTagIds);
        foreach ($tagsToAdd as $tagId) {
            $tag = ProductImportTag::find($tagId);
            if ($tag) {
                $tag->incrementProductsCount();
            }
        }

        // Sincronizar tags
        $this->tags()->sync($newTagIds);
    }

    /**
     * Agregar tag
     */
    public function addTag(string $tagName): void
    {
        $tag = ProductImportTag::findOrCreateByName($tagName);

        if (! $this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->attach($tag->id);
            $tag->incrementProductsCount();
        }
    }

    /**
     * Remover tag
     */
    public function removeTag(string $tagName): void
    {
        $tag = ProductImportTag::where('name', $tagName)->first();

        if ($tag && $this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->detach($tag->id);
            $tag->decrementProductsCount();
        }
    }

    /**
     * Verificar si tiene tag
     */
    public function hasTag(string $tagName): bool
    {
        return $this->tags()->where('name', $tagName)->exists();
    }

    /**
     * Scopes
     */
    public function scopeSimpleProducts($query)
    {
        return $query->where('importable_type', Product::class);
    }

    public function scopeCombinations($query)
    {
        return $query->where('importable_type', Combination::class);
    }

    public function scopePendingSync($query)
    {
        return $query->where('sync_pending', true);
    }

    public function scopeActive($query)
    {
        return $query->where('activo', 1);
    }

    public function scopeSecondHand($query)
    {
        return $query->where('es_segunda_mano', 1);
    }

    public function scopeByEstadoGestion($query, int $estado)
    {
        return $query->where('estado_gestion', $estado);
    }

    public function scopeWeapons($query)
    {
        return $query->where('es_arma', 1);
    }

    public function scopeAmmunition($query)
    {
        return $query->where('es_cartucho', 1);
    }

    public function scopeWithTag($query, string $tagName)
    {
        return $query->whereHas('tags', function ($q) use ($tagName) {
            $q->where('name', $tagName);
        });
    }

    public function scopeWithAnyTag($query, array $tagNames)
    {
        return $query->whereHas('tags', function ($q) use ($tagNames) {
            $q->whereIn('name', $tagNames);
        });
    }

    public function scopeWithAllTags($query, array $tagNames)
    {
        foreach ($tagNames as $tagName) {
            $query->whereHas('tags', function ($q) use ($tagName) {
                $q->where('name', $tagName);
            });
        }

        return $query;
    }
}
