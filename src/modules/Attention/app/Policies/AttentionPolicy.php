<?php

namespace Modules\Attention\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionDepartment;

class AttentionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any peticiones.
     */
    public function viewAny(User $user): bool
    {
        // Super settings can view all
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Users with attention.view permission can view their assigned/department requests
        return $user->hasPermissionTo('attention.view');
    }

    /**
     * Determine if the user can view a specific peticiones.
     */
    public function view(User $user, Attention $attention): bool
    {
        // Super settings can view all
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Creator can always view their own request
        if ($attention->user_id === $user->id) {
            return true;
        }

        // Assigned user can view
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member can view
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        // Users with view all permission
        if ($user->hasPermissionTo('attention.view-all')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create peticiones.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create peticiones
        return true;
    }

    /**
     * Determine if the user can update a peticiones.
     */
    public function update(User $user, Attention $attention): bool
    {
        // Super settings can update all
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Cannot update closed requests
        if ($attention->status === AttentionStatus::CLOSED) {
            return false;
        }

        // Creator can update their own request if not resolved
        if ($attention->user_id === $user->id && $attention->status !== AttentionStatus::RESOLVED) {
            return true;
        }

        // Assigned user can update
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member with update permission can update
        if ($this->belongsToAttentionDepartment($user, $attention) &&
            $user->hasPermissionTo('attention.update')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete a peticiones.
     */
    public function delete(User $user, Attention $attention): bool
    {
        // Only super settings can delete
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Users with explicit delete permission
        if ($user->hasPermissionTo('attention.delete')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can fully manage a peticiones.
     */
    public function manage(User $user, Attention $attention): bool
    {
        // Super settings can manage all
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Users with manage permission can fully manage
        if ($user->hasPermissionTo('attention.manage')) {
            return true;
        }

        // Assigned user can manage their assigned request
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member can manage department requests
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can assign peticiones to department/user.
     */
    public function assign(User $user, Attention $attention): bool
    {
        // Super settings can assign
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Users with assign permission
        if ($user->hasPermissionTo('attention.assign')) {
            return true;
        }

        // Department member can reassign within department
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can change status of peticiones.
     */
    public function changeStatus(User $user, Attention $attention): bool
    {
        // Super settings can change status
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Cannot change status of closed requests
        if ($attention->status === AttentionStatus::CLOSED) {
            return false;
        }

        // Assigned user can change status
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member can change status
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        // Users with change status permission
        if ($user->hasPermissionTo('attention.change-status')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can resolve a peticiones.
     */
    public function resolve(User $user, Attention $attention): bool
    {
        // Super settings can resolve
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Cannot resolve already resolved or closed requests
        if (in_array($attention->status, [AttentionStatus::RESOLVED, AttentionStatus::CLOSED])) {
            return false;
        }

        // Assigned user can resolve
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member can resolve
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        // Users with resolve permission
        if ($user->hasPermissionTo('attention.resolve')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can close a peticiones.
     */
    public function close(User $user, Attention $attention): bool
    {
        // Super settings can close
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Cannot close already closed requests
        if ($attention->status === AttentionStatus::CLOSED) {
            return false;
        }

        // Only resolved requests can be closed
        if ($attention->status !== AttentionStatus::RESOLVED) {
            return false;
        }

        // Assigned user can close
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member can close
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        // Users with close permission
        if ($user->hasPermissionTo('attention.close')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can send emails for a peticiones.
     */
    public function sendEmail(User $user, Attention $attention): bool
    {
        // Super settings can send emails
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Assigned user can send emails
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member can send emails
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        // Users with send email permission
        if ($user->hasPermissionTo('attention.send-email')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can manage notes for a peticiones.
     */
    public function manageNotes(User $user, Attention $attention): bool
    {
        // Super settings can manage notes
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Assigned user can manage notes
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member can manage notes
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        // Users with manage notes permission
        if ($user->hasPermissionTo('attention.manage-notes')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can view full history of a peticiones.
     */
    public function viewHistory(User $user, Attention $attention): bool
    {
        // Super settings can view all history
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Must be able to view the attention first
        if (! $this->view($user, $attention)) {
            return false;
        }

        // Assigned user can view history
        if ($attention->assigned_user_id === $user->id) {
            return true;
        }

        // Department member can view history
        if ($this->belongsToAttentionDepartment($user, $attention)) {
            return true;
        }

        // Users with view history permission
        if ($user->hasPermissionTo('attention.view-history')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can view all peticiones without filters.
     */
    public function viewAll(User $user): bool
    {
        // Super settings can view all
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Users with explicit view all permission
        if ($user->hasPermissionTo('attention.view-all')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is super settings.
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-settings') || $user->hasRole('Super Admin');
    }

    /**
     * Check if user belongs to the attention's assigned department.
     */
    protected function belongsToAttentionDepartment(User $user, Attention $attention): bool
    {
        if (! $attention->department_id) {
            return false;
        }

        $department = AttentionDepartment::find($attention->department_id);

        if (! $department) {
            return false;
        }

        return $department->users()
            ->where('users.id', $user->id)
            ->exists();
    }
}
