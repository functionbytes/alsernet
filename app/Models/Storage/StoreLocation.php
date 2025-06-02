<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreLocation extends Model
{
    protected $table = 'store_locations';

    protected $fillable = [
        'store_code',
        'name',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'phone',
        'email',
        'opening_hours',
        'special_hours',
        'accepts_returns',
        'is_active',
        'capacity'
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'special_hours' => 'array',
        'accepts_returns' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'capacity' => 'integer'
    ];

    // Relaciones
    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequestStore::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAcceptsReturns($query)
    {
        return $query->where('accepts_returns', true);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', 'LIKE', "%{$city}%");
    }

    public function scopeByPostalCode($query, $postalCode)
    {
        return $query->where('postal_code', 'LIKE', substr($postalCode, 0, 2) . '%');
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 10)
    {
        // Fórmula de Haversine para calcular distancia
        return $query->selectRaw("*, (
            6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) * sin(radians(latitude))
            )
        ) AS distance", [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radius)
            ->orderBy('distance');
    }

    /**
     * Verificar si está abierto en un momento dado
     */
    public function isOpen($datetime = null): bool
    {
        $datetime = $datetime ?? now();
        $dayOfWeek = strtolower($datetime->format('l'));
        $time = $datetime->format('H:i');

        // Verificar horarios especiales primero
        if ($this->special_hours) {
            $dateStr = $datetime->format('Y-m-d');
            if (isset($this->special_hours[$dateStr])) {
                if ($this->special_hours[$dateStr] === 'closed') {
                    return false;
                }
                // Verificar horario especial
                return $this->isTimeInRange($time, $this->special_hours[$dateStr]);
            }
        }

        // Verificar horario regular
        if (!isset($this->opening_hours[$dayOfWeek])) {
            return false;
        }

        $hours = $this->opening_hours[$dayOfWeek];
        if ($hours === 'closed') {
            return false;
        }

        return $this->isTimeInRange($time, $hours);
    }

    /**
     * Verificar si una hora está en un rango
     */
    private function isTimeInRange($time, $range): bool
    {
        if (is_array($range)) {
            $start = $range['open'] ?? $range[0];
            $end = $range['close'] ?? $range[1];
        } else {
            // Formato "09:00-18:00"
            list($start, $end) = explode('-', $range);
        }

        return $time >= $start && $time <= $end;
    }

    /**
     * Obtener horario de hoy
     */
    public function getTodayHours(): ?array
    {
        $today = now();
        $dayOfWeek = strtolower($today->format('l'));

        // Verificar horarios especiales
        $dateStr = $today->format('Y-m-d');
        if (isset($this->special_hours[$dateStr])) {
            if ($this->special_hours[$dateStr] === 'closed') {
                return ['closed' => true];
            }
            return $this->special_hours[$dateStr];
        }

        // Horario regular
        if (!isset($this->opening_hours[$dayOfWeek])) {
            return ['closed' => true];
        }

        $hours = $this->opening_hours[$dayOfWeek];
        if ($hours === 'closed') {
            return ['closed' => true];
        }

        return is_array($hours) ? $hours : ['open' => explode('-', $hours)[0], 'close' => explode('-', $hours)[1]];
    }

    /**
     * Verificar disponibilidad para una fecha
     */
    public function hasAvailability($date): bool
    {
        $count = $this->returnRequests()
            ->where('expected_delivery_date', $date)
            ->whereIn('status', ['scheduled', 'delivered'])
            ->count();

        return $count < $this->capacity;
    }

    /**
     * Obtener slots disponibles para una fecha
     */
    public function getAvailableSlots($date): int
    {
        $used = $this->returnRequests()
            ->where('expected_delivery_date', $date)
            ->whereIn('status', ['scheduled', 'delivered'])
            ->count();

        return max(0, $this->capacity - $used);
    }

    /**
     * Obtener dirección completa formateada
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postal_code,
            $this->city,
            $this->province,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Obtener URL de Google Maps
     */
    public function getGoogleMapsUrl(): string
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        $address = urlencode($this->getFullAddress());
        return "https://www.google.com/maps/search/?api=1&query={$address}";
    }
}
