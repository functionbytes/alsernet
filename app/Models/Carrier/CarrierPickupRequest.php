<?php

namespace App\Models\Carrier;

use App\Models\Carrier\Carrier;
use App\Models\Return\ReturnRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class
CarrierPickupRequest extends Model
{
    protected $table = 'return_carrier_pickup_requests';

    protected $fillable = [
        'return_request_id',
        'carrier_id',
        'pickup_code',
        'tracking_number',
        'pickup_date',
        'pickup_time_slot',
        'pickup_address',
        'contact_name',
        'contact_phone',
        'contact_email',
        'status',
        'carrier_request',
        'carrier_response',
        'status_message',
        'packages_count',
        'total_weight',
        'dimensions',
        'confirmed_at',
        'collected_at',
        'delivered_at',
        'cancelled_at'
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'pickup_address' => 'array',
        'carrier_request' => 'array',
        'carrier_response' => 'array',
        'dimensions' => 'array',
        'total_weight' => 'decimal:3',
        'confirmed_at' => 'datetime',
        'collected_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    // Estados
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_COLLECTED = 'collected';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    // Relaciones
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeByCarrier($query, $carrierId)
    {
        return $query->where('carrier_id', $carrierId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('pickup_date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('pickup_date', '>=', now()->toDateString())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    /**
     * Marcar como confirmado
     */
    public function markAsConfirmed($pickupCode = null, $response = null): bool
    {
        $data = [
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now()
        ];

        if ($pickupCode) {
            $data['pickup_code'] = $pickupCode;
        }

        if ($response) {
            $data['carrier_response'] = $response;
        }

        return $this->update($data);
    }

    /**
     * Marcar como recogido
     */
    public function markAsCollected($trackingNumber = null): bool
    {
        $data = [
            'status' => self::STATUS_COLLECTED,
            'collected_at' => now()
        ];

        if ($trackingNumber) {
            $data['tracking_number'] = $trackingNumber;
        }

        return $this->update($data);
    }

    /**
     * Marcar como entregado
     */
    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now()
        ]);
    }

    /**
     * Cancelar recogida
     */
    public function cancel($reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'status_message' => $reason
        ]);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed($reason): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'status_message' => $reason
        ]);
    }

    /**
     * Verificar si está en proceso
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [
            self::STATUS_CONFIRMED,
            self::STATUS_IN_TRANSIT,
            self::STATUS_COLLECTED
        ]);
    }

    /**
     * Verificar si está completado
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Verificar si se puede cancelar
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
                self::STATUS_PENDING,
                self::STATUS_CONFIRMED
            ]) && $this->pickup_date > now()->toDateString();
    }

    /**
     * Obtener etiqueta de estado
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_CONFIRMED => 'Confirmada',
            self::STATUS_IN_TRANSIT => 'En tránsito',
            self::STATUS_COLLECTED => 'Recogido',
            self::STATUS_DELIVERED => 'Entregado',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_FAILED => 'Fallida'
        ];

        return $labels[$this->status] ?? 'Desconocido';
    }

    /**
     * Obtener color de estado
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_IN_TRANSIT => 'primary',
            self::STATUS_COLLECTED => 'primary',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_FAILED => 'danger'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /**
     * Generar datos para etiqueta
     */
    public function getLabelData(): array
    {
        return [
            'pickup_code' => $this->pickup_code,
            'tracking_number' => $this->tracking_number,
            'carrier_name' => $this->carrier->name,
            'pickup_date' => $this->pickup_date->format('d/m/Y'),
            'pickup_time' => $this->pickup_time_slot,
            'pickup_address' => $this->pickup_address,
            'contact' => [
                'name' => $this->contact_name,
                'phone' => $this->contact_phone,
                'email' => $this->contact_email
            ],
            'packages' => $this->packages_count,
            'weight' => $this->total_weight,
            'return_number' => $this->returnRequest->getReturnNumber()
        ];
    }
}

