<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_duration_months',
        'max_duration_months',
        'coverage_details',
        'exclusions',
        'cost_percentage',
        'fixed_cost',
        'transferable',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'coverage_details' => 'array',
        'exclusions' => 'array',
        'cost_percentage' => 'decimal:2',
        'fixed_cost' => 'decimal:2',
        'transferable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Garantías de este tipo
     */
    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }

    /**
     * Extensiones de garantía de este tipo
     */
    public function warrantyExtensions()
    {
        return $this->hasMany(WarrantyExtension::class);
    }

    /**
     * Scope para tipos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordenado por prioridad
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Calcular costo de garantía para un producto
     */
    public function calculateCost(float $productPrice, int $months = null): float
    {
        $months = $months ?? $this->default_duration_months;

        $percentageCost = ($productPrice * $this->cost_percentage) / 100;
        $fixedCost = $this->fixed_cost;

        // Ajustar por duración si es diferente a la por defecto
        if ($months !== $this->default_duration_months && $this->default_duration_months > 0) {
            $ratio = $months / $this->default_duration_months;
            $percentageCost *= $ratio;
            $fixedCost *= $ratio;
        }

        return $percentageCost + $fixedCost;
    }

    /**
     * Verificar si cubre un tipo de problema
     */
    public function covers(string $issueType): bool
    {
        if (!$this->coverage_details) {
            return false;
        }

        $coveredIssues = $this->coverage_details['issues'] ?? [];
        return in_array($issueType, $coveredIssues);
    }

    /**
     * Verificar si excluye un tipo de problema
     */
    public function excludes(string $issueType): bool
    {
        if (!$this->exclusions) {
            return false;
        }

        $excludedIssues = $this->exclusions['issues'] ?? [];
        return in_array($issueType, $excludedIssues);
    }

    /**
     * Obtener descripción de cobertura
     */
    public function getCoverageDescription(): string
    {
        $coverage = $this->coverage_details['description'] ?? '';

        if (!$coverage && isset($this->coverage_details['issues'])) {
            $issues = $this->coverage_details['issues'];
            $coverage = 'Cubre: ' . implode(', ', $issues);
        }

        return $coverage;
    }
}
