<?php

namespace Modules\Supplier\Models\Sync;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Modules\Supplier\Models\Supplier\Supplier;

class ExcludedContentSupplier extends Model
{
    protected $table = 'supplier_excluded_content_suppliers';

    protected $fillable = ['supplier_id', 'reason'];

    protected $casts = ['supplier_id' => 'integer'];

    const CACHE_KEY = 'supplier:excluded_content_supplier_ids';

    const CACHE_TTL = 600; // 10 min

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Devuelve el set de IDs de proveedor excluidos de la generación
     * automática de contenido IA (cacheado).
     *
     * @return array<int, bool> clave = supplier_id, valor = true
     */
    public static function getExcludedSet(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return static::pluck('supplier_id')
                ->mapWithKeys(fn ($id) => [$id => true])
                ->all();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
