<?php

namespace Modules\HelpdeskIntegration\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
