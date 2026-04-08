<?php

namespace Modules\Attention\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttentionRoutingRule extends Model
{
    protected $table = 'attention_routing_rules';

    protected $fillable = [
        'name',
        'priority',
        'condition_type_id',
        'condition_category_id',
        'condition_sede_id',
        'assign_to_user_id',
        'assign_to_department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function conditionType(): BelongsTo
    {
        return $this->belongsTo(AttentionType::class, 'condition_type_id');
    }

    public function conditionCategory(): BelongsTo
    {
        return $this->belongsTo(AttentionCategory::class, 'condition_category_id');
    }

    public function conditionSede(): BelongsTo
    {
        return $this->belongsTo(AttentionSede::class, 'condition_sede_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assign_to_user_id');
    }

    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(AttentionDepartment::class, 'assign_to_department_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('priority');
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Determines whether this rule applies to the given attention.
     */
    public function matches(Attention $attention): bool
    {
        if ($this->condition_type_id && $attention->type_id != $this->condition_type_id) {
            return false;
        }

        if ($this->condition_category_id && $attention->category_id != $this->condition_category_id) {
            return false;
        }

        if ($this->condition_sede_id && $attention->sede_id != $this->condition_sede_id) {
            return false;
        }

        return true;
    }

    /**
     * Returns a human-readable summary of this rule's conditions.
     */
    public function conditionsSummary(): string
    {
        $parts = [];

        if ($this->condition_type_id) {
            $parts[] = 'Tipo: '.($this->conditionType?->name ?? '—');
        }

        if ($this->condition_category_id) {
            $parts[] = 'Categoría: '.($this->conditionCategory?->name ?? '—');
        }

        if ($this->condition_sede_id) {
            $parts[] = 'Sede: '.($this->conditionSede?->name ?? '—');
        }

        return $parts ? implode(', ', $parts) : 'Todas las PQRSF';
    }
}
