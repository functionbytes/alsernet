<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'allow_returns',
        'default_return_days',
        'return_policy_text',
        'return_restrictions',
    ];

    protected $casts = [
        'allow_returns' => 'boolean',
        'return_restrictions' => 'array',
    ];

    /**
     * Productos de esta categoría
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Reglas de devolución específicas de esta categoría
     */
    public function returnRules()
    {
        return $this->hasMany(ProductReturnRule::class);
    }

    /**
     * Obtener la regla de devolución activa para esta categoría
     */
    public function getActiveReturnRule(): ?ProductReturnRule
    {
        return $this->returnRules()->active()->byPriority()->first();
    }

    /**
     * Verificar si los productos de esta categoría son retornables
     */
    public function allowsReturns(): bool
    {
        $rule = $this->getActiveReturnRule();
        return $rule ? $rule->is_returnable : $this->allow_returns;
    }

    /**
     * Obtener período de devolución para esta categoría
     */
    public function getReturnPeriod(): int
    {
        $rule = $this->getActiveReturnRule();
        return $rule && $rule->return_period_days ? $rule->return_period_days : $this->default_return_days;
    }
}
