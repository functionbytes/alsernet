<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'return_rule_id',
        'validation_status',
        'validation_results',
        'failure_reasons',
        'admin_notes',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'validation_results' => 'array',
        'validated_at' => 'datetime',
    ];

    /**
     * Estados de validación disponibles
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PASSED = 'passed';
    const STATUS_FAILED = 'failed';
    const STATUS_MANUAL_REVIEW = 'manual_review';

    /**
     * Relación con orden
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación con producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con regla de devolución
     */
    public function returnRule()
    {
        return $this->belongsTo(ProductReturnRule::class, 'return_rule_id');
    }

    /**
     * Usuario que validó
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Verificar si la validación pasó
     */
    public function hasPassed(): bool
    {
        return $this->validation_status === self::STATUS_PASSED;
    }

    /**
     * Verificar si la validación falló
     */
    public function hasFailed(): bool
    {
        return $this->validation_status === self::STATUS_FAILED;
    }

    /**
     * Verificar si requiere revisión manual
     */
    public function requiresManualReview(): bool
    {
        return $this->validation_status === self::STATUS_MANUAL_REVIEW;
    }

    /**
     * Obtener errores de validación
     */
    public function getValidationErrors(): array
    {
        return $this->validation_results['errors'] ?? [];
    }

    /**
     * Obtener advertencias de validación
     */
    public function getValidationWarnings(): array
    {
        return $this->validation_results['warnings'] ?? [];
    }
}
