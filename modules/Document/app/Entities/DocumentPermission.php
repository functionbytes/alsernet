<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Document Permission Model
 * Manages dynamic permissions that can be assigned to validator groups
 * Similar to Spatie Permission but scoped to Document module
 */
class DocumentPermission extends Model
{
    protected $fillable = [
        'name',
        'label',
        'category',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Validator groups that have this permission
     */
    public function validatorGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            DocumentValidatorGroup::class,
            'document_validator_group_permission',
            'permission_id',
            'validator_group_id'
        )->withTimestamps();
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Only active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get all permissions grouped by category
     */
    public static function getAllGroupedByCategory(): array
    {
        return self::active()
            ->ordered()
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Check if permission exists by name
     */
    public static function findByName(string $name): ?self
    {
        return self::where('name', $name)->first();
    }

    /**
     * Create permission if it doesn't exist
     */
    public static function findOrCreateByName(string $name, array $attributes = []): self
    {
        $permission = self::findByName($name);

        if (! $permission) {
            $permission = self::create(array_merge([
                'name' => $name,
                'label' => ucfirst(str_replace('-', ' ', $name)),
            ], $attributes));
        }

        return $permission;
    }
}
