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

    public static function createFromErpData(array $data, array $orderClientData = []): self
    {
        $clientData = !empty($orderClientData) ? $orderClientData : $data;

        return self::create([
            'erp_client_id' => $erpData['idcliente'] ?? $clientData['idcliente'],
            'fullname' => $erpData['nombre'] ." ".$erpData['apellidos'] ?? $clientData['nombre'] ." ".$clientData['apellidos'],
            'firstname' => $erpData['nombre'] ?? $clientData['nombre'],
            'lastname' => $erpData['apellidos'] ?? $clientData['apellidos'],
            'email' => $erpData['email'] ?? $clientData['email'],
            'subscriber_id' => null,
        ]);
    }

    public function updateFromErpData(array $data): bool
    {
        return $this->update([
            'erp_client_id' => $data['idcliente'],
            'fullname' => $data['nombre'] ." ".$data['apellidos'],
            'firstname' => $data['nombre'],
            'lastname' => $data['apellidos'] ,
            'email' => $data['email'],
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
