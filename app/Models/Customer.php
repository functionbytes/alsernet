<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';

    protected $fillable = [
        'erp_client_id',
        'name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'cif',
        'card_id',
        'category_id',
        'language_id',
        'creation_date',
        'status',
        'accept_commercial_info',
        'accept_data_sharing',
        'accept_legitimate_interest',
        'lopd_acceptance_date',
        'catalogs',
        'erp_data'
    ];

    protected $casts = [
        'creation_date' => 'date',
        'lopd_acceptance_date' => 'date',
        'accept_commercial_info' => 'boolean',
        'accept_data_sharing' => 'boolean',
        'accept_legitimate_interest' => 'boolean',
        'catalogs' => 'json',
        'erp_data' => 'json'
    ];

    // Relaciones
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(\App\Models\Return\ReturnRequest::class, 'customer_id', 'id');
    }

    // Scopes
    public function scopeByErpId($query, $erpId)
    {
        return $query->where('erp_client_id', $erpId);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Métodos auxiliares
    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . $this->last_name);
    }

    public function getTotalOrdersAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getTotalReturnsAttribute(): int
    {
        return $this->returns()->count();
    }

    public function getReturnRateAttribute(): float
    {
        $totalOrders = $this->total_orders;
        return $totalOrders > 0 ? ($this->total_returns / $totalOrders) * 100 : 0;
    }

    public function hasActiveCatalogs(): bool
    {
        return !empty($this->catalogs) &&
            collect($this->catalogs)->where('estado', '1')->isNotEmpty();
    }

    /**
     * Crear cliente desde datos ERP
     */
    public static function createFromErpData(array $erpData, array $orderClientData = []): self
    {
        // Usar datos del pedido si están disponibles, sino usar datos completos del ERP
        $clientData = !empty($orderClientData) ? $orderClientData : $erpData;

        return self::create([
            'erp_client_id' => $erpData['idcliente'] ?? $clientData['idcliente'],
            'name' => $erpData['nombre'] ?? $clientData['nombre'],
            'last_name' => $erpData['apellidos'] ?? $clientData['apellidos'],
            'email' => $erpData['email'] ?? $clientData['email'],
            'cif' => $erpData['cif'] ?? $clientData['cif'],
            'card_id' => $erpData['idtarjeta'] ?? null,
            'category_id' => $erpData['idcategoria_cliente'] ?? null,
            'language_id' => $erpData['ididioma'] ?? 1,
            'creation_date' => $erpData['fcreacion'] ?? now(),
            'status' => 'active',
            'accept_commercial_info' => !($erpData['no_informacion_comercial_lopd'] ?? false),
            'accept_data_sharing' => !($erpData['no_datos_a_terceros_lopd'] ?? false),
            'accept_legitimate_interest' => $erpData['tiene_interes_legitimo_lopd'] ?? false,
            'lopd_acceptance_date' => $erpData['faceptacion_lopd'] ?? null,
            'catalogs' => $erpData['cliente_catalogo']['resource'] ?? null,
            'erp_data' => $erpData
        ]);
    }

    /**
     * Actualizar desde datos ERP
     */
    public function updateFromErpData(array $erpData): bool
    {
        return $this->update([
            'name' => $erpData['nombre'] ?? $this->name,
            'last_name' => $erpData['apellidos'] ?? $this->last_name,
            'email' => $erpData['email'] ?? $this->email,
            'cif' => $erpData['cif'] ?? $this->cif,
            'accept_commercial_info' => !($erpData['no_informacion_comercial_lopd'] ?? false),
            'accept_data_sharing' => !($erpData['no_datos_a_terceros_lopd'] ?? false),
            'accept_legitimate_interest' => $erpData['tiene_interes_legitimo_lopd'] ?? false,
            'catalogs' => $erpData['cliente_catalogo']['resource'] ?? $this->catalogs,
            'erp_data' => $erpData,
            'updated_at' => now()
        ]);
    }

    /**
     * Obtener información de catálogos activos
     */
    public function getActiveCatalogs(): array
    {
        if (empty($this->catalogs)) {
            return [];
        }

        return collect($this->catalogs)
            ->where('estado', '1')
            ->map(function($catalog) {
                return [
                    'id' => $catalog['idcatalogo'],
                    'subscription_date' => $catalog['fsuscripcion'] ?? null,
                    'status' => $catalog['estado']
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Verificar si el cliente puede hacer devoluciones
     */
    public function canMakeReturns(): bool
    {
        return $this->status === 'active' &&
            $this->hasActiveCatalogs() &&
            $this->accept_data_sharing;
    }

    /**
     * Obtener resumen del cliente
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'erp_client_id' => $this->erp_client_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cif' => $this->cif,
            'total_orders' => $this->total_orders,
            'total_returns' => $this->total_returns,
            'return_rate' => round($this->return_rate, 2) . '%',
            'active_catalogs' => $this->getActiveCatalogs(),
            'can_make_returns' => $this->canMakeReturns(),
            'member_since' => $this->creation_date?->format('d/m/Y')
        ];
    }
}
