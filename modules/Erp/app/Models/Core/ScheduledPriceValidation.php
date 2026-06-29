<?php

namespace Modules\Erp\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPriceValidation extends Model
{
    protected $fillable = [
        'id_specific_price',
        'id_product',
        'id_product_attribute',
        'id_country',
        'reference',
        'valid_from',
        'valid_to',
        'status',
        'executed_at',
        'executions_count',
        'last_execution_at',
        'next_execution_at',
        'gestion_price',
        'prestashop_price',
        'difference',
        'has_difference',
        'price_validation_id',
        'last_error',
        'execution_log',
    ];

    protected $casts = [
        'id_specific_price' => 'integer',
        'id_product' => 'integer',
        'id_product_attribute' => 'integer',
        'id_country' => 'integer',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'executed_at' => 'datetime',
        'last_execution_at' => 'datetime',
        'next_execution_at' => 'datetime',
        'gestion_price' => 'decimal:2',
        'prestashop_price' => 'decimal:2',
        'difference' => 'decimal:2',
        'has_difference' => 'boolean',
        'execution_log' => 'array',
    ];

    /**
     * Relación con la validación creada
     */
    public function validation(): BelongsTo
    {
        return $this->belongsTo(PriceValidation::class, 'price_validation_id');
    }

    /**
     * Agregar entrada al log de ejecución
     */
    public function addExecutionLog(string $status, ?string $message = null, ?array $data = null): void
    {
        $log = $this->execution_log ?? [];

        $log[] = [
            'timestamp' => now()->toIso8601String(),
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];

        // Mantener solo las últimas 20 ejecuciones
        if (count($log) > 20) {
            $log = array_slice($log, -20);
        }

        $this->execution_log = $log;
        $this->save();
    }

    /**
     * Marcar como ejecutado
     */
    public function markAsExecuted(?float $gestionPrice = null, ?float $prestashopPrice = null): void
    {
        $this->executions_count++;
        $this->last_execution_at = now();
        $this->executed_at = $this->executed_at ?? now();

        if ($gestionPrice !== null) {
            $this->gestion_price = $gestionPrice;
        }

        if ($prestashopPrice !== null) {
            $this->prestashop_price = $prestashopPrice;
        }

        if ($gestionPrice !== null && $prestashopPrice !== null) {
            $this->difference = $prestashopPrice - $gestionPrice;
            $this->has_difference = abs($this->difference) > 0.01; // Tolerancia de 1 céntimo
        }

        $this->save();
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed(string $error): void
    {
        $this->status = 'failed';
        $this->last_error = $error;
        $this->addExecutionLog('failed', $error);
        $this->save();
    }

    /**
     * Marcar como completado
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->save();
    }

    /**
     * Scopes
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePendingExecution($query)
    {
        return $query->where('status', 'scheduled')
            ->where(function ($q) {
                $q->whereNull('next_execution_at')
                    ->orWhere('next_execution_at', '<=', now());
            });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'scheduled')
            ->where(function ($q) {
                $q->where('valid_from', '<=', now())
                    ->orWhereNull('valid_from');
            })
            ->where(function ($q) {
                $q->where('valid_to', '>=', now())
                    ->orWhereNull('valid_to');
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('valid_to')
            ->where('valid_to', '<', now());
    }
}
