<?php

namespace App\Models\Carrier;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    protected $table = 'carriers';

    protected $fillable = [
        'code',
        'name',
        'type',
        'logo_path',
        'tracking_url',
        'is_active',
        'api_endpoint',
        'api_key',
        'api_secret',
        'api_username',
        'api_config',
        'services',
        'zones',
        'max_weight',
        'working_hours',
        'base_cost',
        'cost_rules'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_config' => 'array',
        'services' => 'array',
        'zones' => 'array',
        'working_hours' => 'array',
        'cost_rules' => 'array',
        'max_weight' => 'decimal:2',
        'base_cost' => 'decimal:2'
    ];

    // Tipos de carrier
    const TYPE_AGENCY = 'agency';
    const TYPE_PICKUP = 'pickup';
    const TYPE_STORE = 'store';
    const TYPE_INPOST = 'inpost';

    // Relaciones
    public function pickupRequests(): HasMany
    {
        return $this->hasMany(CarrierPickupRequest::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForPickup($query)
    {
        return $query->whereIn('type', [self::TYPE_PICKUP, self::TYPE_INPOST]);
    }

    /**
     * Verificar si el carrier está disponible para un código postal
     */
    public function isAvailableForPostalCode($postalCode): bool
    {
        if (empty($this->zones)) {
            return true; // Si no hay zonas definidas, está disponible en todas
        }

        // Verificar si el código postal está en las zonas permitidas
        foreach ($this->zones as $zone) {
            if (isset($zone['postal_codes'])) {
                foreach ($zone['postal_codes'] as $pattern) {
                    if (fnmatch($pattern, $postalCode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Obtener horarios disponibles para una fecha
     */
    public function getAvailableTimeSlots($date): array
    {
        $dayOfWeek = strtolower($date->format('l'));

        if (!isset($this->working_hours[$dayOfWeek])) {
            return [];
        }

        $slots = [];
        $hours = $this->working_hours[$dayOfWeek];

        if (isset($hours['slots'])) {
            foreach ($hours['slots'] as $slot) {
                $slots[] = [
                    'start' => $slot['start'],
                    'end' => $slot['end'],
                    'label' => $slot['start'] . ' - ' . $slot['end']
                ];
            }
        }

        return $slots;
    }

    /**
     * Calcular coste de envío
     */
    public function calculateCost($weight, $postalCode, $service = 'standard'): float
    {
        $cost = $this->base_cost;

        if (!empty($this->cost_rules)) {
            // Aplicar reglas de peso
            if (isset($this->cost_rules['weight_ranges'])) {
                foreach ($this->cost_rules['weight_ranges'] as $range) {
                    if ($weight >= $range['min'] && $weight <= $range['max']) {
                        $cost += $range['cost'];
                        break;
                    }
                }
            }

            // Aplicar reglas de zona
            if (isset($this->cost_rules['zone_surcharges'])) {
                foreach ($this->cost_rules['zone_surcharges'] as $zone) {
                    if (in_array(substr($postalCode, 0, 2), $zone['prefixes'])) {
                        $cost += $zone['surcharge'];
                        break;
                    }
                }
            }

            // Aplicar reglas de servicio
            if (isset($this->cost_rules['service_costs'][$service])) {
                $cost += $this->cost_rules['service_costs'][$service];
            }
        }

        return $cost;
    }

    /**
     * Obtener URL de tracking
     */
    public function getTrackingUrl($trackingNumber): ?string
    {
        if (!$this->tracking_url) {
            return null;
        }

        return str_replace('{tracking}', $trackingNumber, $this->tracking_url);
    }

    /**
     * Verificar si requiere autenticación API
     */
    public function requiresApiAuth(): bool
    {
        return !empty($this->api_key) || !empty($this->api_username);
    }

    /**
     * Obtener configuración API completa
     */
    public function getApiConfig(): array
    {
        $config = [
            'endpoint' => $this->api_endpoint,
            'auth' => []
        ];

        if ($this->api_key) {
            $config['auth']['key'] = $this->api_key;
        }

        if ($this->api_secret) {
            $config['auth']['secret'] = $this->api_secret;
        }

        if ($this->api_username) {
            $config['auth']['username'] = $this->api_username;
        }

        if ($this->api_config) {
            $config = array_merge($config, $this->api_config);
        }

        return $config;
    }
}
