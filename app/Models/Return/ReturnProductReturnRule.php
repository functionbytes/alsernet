<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ReturnProductReturnRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_id',
        'rule_type',
        'is_returnable',
        'return_period_days',
        'max_return_percentage',
        'conditions',
        'excluded_reasons',
        'requires_original_packaging',
        'requires_receipt',
        'allow_partial_return',
        'special_instructions',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_returnable' => 'boolean',
        'conditions' => 'array',
        'excluded_reasons' => 'array',
        'requires_original_packaging' => 'boolean',
        'requires_receipt' => 'boolean',
        'allow_partial_return' => 'boolean',
        'is_active' => 'boolean',
        'max_return_percentage' => 'decimal:2',
    ];

    /**
     * Relación con categoría
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación con producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Validaciones de devolución
     */
    public function returnValidations()
    {
        return $this->hasMany(ReturnValidation::class, 'return_rule_id');
    }

    /**
     * Scope para reglas activas
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para reglas por tipo
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope para ordenar por prioridad
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Obtener regla aplicable para un producto
     */
    public static function getApplicableRule(Product $product): ?self
    {
        // 1. Buscar regla específica del producto (mayor prioridad)
        $productRule = static::active()
            ->where('product_id', $product->id)
            ->byPriority()
            ->first();

        if ($productRule) {
            return $productRule;
        }

        // 2. Buscar regla de la categoría del producto
        if ($product->category_id) {
            $categoryRule = static::active()
                ->where('category_id', $product->category_id)
                ->byPriority()
                ->first();

            if ($categoryRule) {
                return $categoryRule;
            }
        }

        // 3. Buscar regla global
        $globalRule = static::active()
            ->byType('global')
            ->byPriority()
            ->first();

        return $globalRule;
    }

    /**
     * Verificar si un producto es retornable
     */
    public static function isProductReturnable(Product $product): bool
    {
        $rule = static::getApplicableRule($product);
        return $rule ? $rule->is_returnable : true; // Por defecto retornable
    }

    /**
     * Obtener período de devolución para un producto
     */
    public static function getReturnPeriod(Product $product): ?int
    {
        $rule = static::getApplicableRule($product);

        if (!$rule) {
            return $product->category?->default_return_days ?? 30;
        }

        return $rule->return_period_days;
    }

    /**
     * Validar si una devolución cumple las condiciones
     */
    public function validateReturn(array $returnData): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Verificar si el producto es retornable
        if (!$this->is_returnable) {
            $results['valid'] = false;
            $results['errors'][] = 'Este producto no es retornable';
            return $results;
        }

        // Verificar período de devolución
        if ($this->return_period_days && isset($returnData['purchase_date'])) {
            $purchaseDate = \Carbon\Carbon::parse($returnData['purchase_date']);
            $daysSincePurchase = $purchaseDate->diffInDays(now());

            if ($daysSincePurchase > $this->return_period_days) {
                $results['valid'] = false;
                $results['errors'][] = "El período de devolución de {$this->return_period_days} días ha expirado";
            }
        }

        // Verificar empaque original si es requerido
        if ($this->requires_original_packaging && !($returnData['has_original_packaging'] ?? false)) {
            $results['valid'] = false;
            $results['errors'][] = 'Se requiere el empaque original para esta devolución';
        }

        // Verificar recibo si es requerido
        if ($this->requires_receipt && !($returnData['has_receipt'] ?? false)) {
            $results['valid'] = false;
            $results['errors'][] = 'Se requiere el recibo de compra para esta devolución';
        }

        // Verificar razón de devolución excluida
        if ($this->excluded_reasons && isset($returnData['reason'])) {
            if (in_array($returnData['reason'], $this->excluded_reasons)) {
                $results['valid'] = false;
                $results['errors'][] = 'La razón de devolución seleccionada no es válida para este producto';
            }
        }

        // Verificar condiciones adicionales
        if ($this->conditions) {
            $conditionResults = $this->validateConditions($returnData);
            $results['valid'] = $results['valid'] && $conditionResults['valid'];
            $results['errors'] = array_merge($results['errors'], $conditionResults['errors']);
            $results['warnings'] = array_merge($results['warnings'], $conditionResults['warnings']);
        }

        return $results;
    }

    /**
     * Validar condiciones específicas
     */
    protected function validateConditions(array $returnData): array
    {
        $results = ['valid' => true, 'errors' => [], 'warnings' => []];

        foreach ($this->conditions as $condition) {
            switch ($condition['type'] ?? '') {
                case 'min_days_owned':
                    if (isset($returnData['purchase_date'])) {
                        $daysOwned = \Carbon\Carbon::parse($returnData['purchase_date'])->diffInDays(now());
                        if ($daysOwned < ($condition['value'] ?? 0)) {
                            $results['valid'] = false;
                            $results['errors'][] = "Debe poseer el producto por al menos {$condition['value']} días antes de devolverlo";
                        }
                    }
                    break;

                case 'max_usage_percentage':
                    $usagePercentage = $returnData['usage_percentage'] ?? 0;
                    if ($usagePercentage > ($condition['value'] ?? 100)) {
                        $results['valid'] = false;
                        $results['errors'][] = "El producto no puede tener más del {$condition['value']}% de uso";
                    }
                    break;

                case 'requires_unopened':
                    if ($condition['value'] && ($returnData['is_opened'] ?? false)) {
                        $results['valid'] = false;
                        $results['errors'][] = 'El producto debe estar sin abrir';
                    }
                    break;

                case 'seasonal_restriction':
                    $currentMonth = now()->month;
                    $restrictedMonths = $condition['months'] ?? [];
                    if (in_array($currentMonth, $restrictedMonths)) {
                        $results['warnings'][] = 'Las devoluciones en esta época pueden tener procesamiento extendido';
                    }
                    break;
            }
        }

        return $results;
    }

    /**
     * Obtener texto explicativo de la regla
     */
    public function getDescriptionAttribute(): string
    {
        $description = [];

        if (!$this->is_returnable) {
            return 'Producto no retornable';
        }

        if ($this->return_period_days) {
            $description[] = "Período de devolución: {$this->return_period_days} días";
        } else {
            $description[] = "Sin límite de tiempo para devolución";
        }

        if ($this->max_return_percentage < 100) {
            $description[] = "Reembolso máximo: {$this->max_return_percentage}%";
        }

        if ($this->requires_original_packaging) {
            $description[] = "Requiere empaque original";
        }

        if ($this->requires_receipt) {
            $description[] = "Requiere recibo";
        }

        return implode(' • ', $description);
    }
}
