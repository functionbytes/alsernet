<?php

namespace Modules\HelpdeskIntegration\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskIntegration\Database\Factories\IntegrationProviderFactory;

/**
 * Fila de catalogo para una plataforma de integracion (nativa o custom).
 * `driver` identifica la clase registrada en IntegrationDriverRegistry;
 * cuando es null, el proveedor es custom (sin logica de busqueda propia).
 */
class IntegrationProvider extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_integration_providers';

    protected $fillable = [
        'platform',
        'driver',
        'label',
        'icon',
        'color',
        'is_active',
        'is_linkable',
        'is_critical',
        'search_types',
        'credentials',
        'config',
        'sort_order',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_linkable' => 'boolean',
            'is_critical' => 'boolean',
            'search_types' => 'array',
            'credentials' => 'encrypted:array',
            'config' => 'array',
            'sort_order' => 'integer',
        ];
    }

    private const CACHE_KEY = 'helpdeskintegration:providers:all';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Catálogo completo ordenado (mismo criterio que usaban por separado
     * CustomerIntegrationService::buildPayload() y el panel derecho del
     * inbox), cacheado sin TTL — tabla de tamaño fijo (~5-15 filas) que solo
     * cambia cuando un admin guarda algo en Settings → Integraciones, y se
     * invalida explícitamente arriba (saved/deleted) en vez de expirar sola.
     * Antes se re-consultaba por SQL en cada apertura/cambio de conversación
     * del inbox, sin ningún beneficio de frescura.
     */
    public static function allCached(): Collection
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->orderBy('sort_order')->get(),
        );
    }

    public function isNative(): bool
    {
        return $this->driver !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLinkable(Builder $query): Builder
    {
        return $query->where('is_linkable', true);
    }

    protected static function newFactory(): IntegrationProviderFactory
    {
        return IntegrationProviderFactory::new();
    }
}
