<?php

namespace Modules\Attention\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Attention\Database\Factories\AttentionDepartmentFactory;

/**
 * Departamentos o áreas para asignación de peticiones
 */
class AttentionDepartment extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): Factory
    {
        return AttentionDepartmentFactory::new();
    }

    protected $table = 'attention_departments';

    protected $fillable = [
        'name',
        'description',
        'email',
        'responsible_name',
        'responsible_email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Attentions assigned to this department
     */
    public function attentions(): HasMany
    {
        return $this->hasMany(Attention::class, 'department_id');
    }

    /**
     * Users assigned to this department
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'attention_department_user', 'department_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get active users assigned to this department
     */
    public function getActiveUsers()
    {
        return $this->users()->where('available', 1)->get();
    }

    /**
     * Assign a user to this department
     */
    public function assignUser(int $userId): void
    {
        if (! $this->users()->where('user_id', $userId)->exists()) {
            $this->users()->attach($userId);
        }
    }

    /**
     * Remove a user from this department
     */
    public function removeUser(int $userId): void
    {
        $this->users()->detach($userId);
    }

    /**
     * Scope active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
