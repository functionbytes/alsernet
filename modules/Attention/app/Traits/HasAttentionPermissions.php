<?php

namespace Modules\Attention\Traits;

use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionDepartment;

trait HasAttentionPermissions
{
    /**
     * Check if user can manage a specific attention.
     */
    public function canManageAttention(Attention $attention): bool
    {
        return $this->can('manage', $attention);
    }

    /**
     * Check if user is assigned to the attention.
     */
    public function isAssignedTo(Attention $attention): bool
    {
        return $attention->assigned_user_id === $this->id;
    }

    /**
     * Check if user belongs to the attention's department.
     */
    public function belongsToAttentionDepartment(Attention $attention): bool
    {
        if (! $attention->department_id) {
            return false;
        }

        $department = AttentionDepartment::find($attention->department_id);

        if (! $department) {
            return false;
        }

        return $department->users()
            ->where('users.id', $this->id)
            ->exists();
    }

    /**
     * Check if user is the creator of the attention.
     */
    public function isAttentionCreator(Attention $attention): bool
    {
        return $attention->user_id === $this->id;
    }

    /**
     * Check if user can view the attention.
     */
    public function canViewAttention(Attention $attention): bool
    {
        return $this->can('view', $attention);
    }

    /**
     * Check if user can update the attention.
     */
    public function canUpdateAttention(Attention $attention): bool
    {
        return $this->can('update', $attention);
    }

    /**
     * Check if user can delete the attention.
     */
    public function canDeleteAttention(Attention $attention): bool
    {
        return $this->can('delete', $attention);
    }

    /**
     * Check if user can assign the attention.
     */
    public function canAssignAttention(Attention $attention): bool
    {
        return $this->can('assign', $attention);
    }

    /**
     * Check if user can change status of the attention.
     */
    public function canChangeAttentionStatus(Attention $attention): bool
    {
        return $this->can('changeStatus', $attention);
    }

    /**
     * Check if user can resolve the attention.
     */
    public function canResolveAttention(Attention $attention): bool
    {
        return $this->can('resolve', $attention);
    }

    /**
     * Check if user can close the attention.
     */
    public function canCloseAttention(Attention $attention): bool
    {
        return $this->can('close', $attention);
    }

    /**
     * Check if user can send emails for the attention.
     */
    public function canSendAttentionEmail(Attention $attention): bool
    {
        return $this->can('sendEmail', $attention);
    }

    /**
     * Check if user can manage notes for the attention.
     */
    public function canManageAttentionNotes(Attention $attention): bool
    {
        return $this->can('manageNotes', $attention);
    }

    /**
     * Check if user can view history of the attention.
     */
    public function canViewAttentionHistory(Attention $attention): bool
    {
        return $this->can('viewHistory', $attention);
    }

    /**
     * Check if user can view all attentions without filters.
     */
    public function canViewAllAttentions(): bool
    {
        return $this->can('viewAll', Attention::class);
    }

    /**
     * Get all attentions the user has access to.
     */
    public function accessibleAttentions()
    {
        // Super settings sees all
        if ($this->hasRole('super-settings') || $this->hasRole('Super Admin')) {
            return Attention::query();
        }

        // If user can view all
        if ($this->canViewAllAttentions()) {
            return Attention::query();
        }

        // Build query for user's accessible attentions
        return Attention::query()->where(function ($query) {
            // Created by user
            $query->where('user_id', $this->id)
                // Assigned to user
                ->orWhere('assigned_user_id', $this->id)
                // Assigned to user's departments
                ->orWhereIn('department_id', function ($subQuery) {
                    $subQuery->select('attention_department_id')
                        ->from('attention_department_user')
                        ->where('user_id', $this->id);
                });
        });
    }

    /**
     * Get departments where user is a member.
     */
    public function attentionDepartments()
    {
        return $this->belongsToMany(
            AttentionDepartment::class,
            'attention_department_user',
            'user_id',
            'attention_department_id'
        )->withTimestamps();
    }
}
