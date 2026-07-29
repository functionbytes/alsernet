<?php

namespace Modules\Supplier\Models\Sync;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ExcludedErpGroup extends Model
{
    protected $table = 'supplier_excluded_erp_groups';

    protected $fillable = ['erp_id', 'label', 'reason'];

    protected $casts = ['erp_id' => 'integer'];

    const CACHE_KEY = 'supplier:excluded_erp_group_ids';
    const CACHE_TTL = 600; // 10 min

    /**
     * Devuelve el set de IDs excluidos (cacheado).
     * Incluye erp_id de todos los registros para comparar contra
     * idgrupo_cl, idsubfamilia_cl e idfamilia_cl del ERP.
     *
     * @return array<int, bool>  clave = erp_id, valor = true
     */
    public static function getExcludedSet(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return static::pluck('erp_id')
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
