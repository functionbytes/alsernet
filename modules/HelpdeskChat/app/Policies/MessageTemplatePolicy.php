<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\MessageTemplate;

class MessageTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MessageTemplate $messageTemplate): bool
    {
        // User can view if template belongs to their account AND is either public OR created by them
        return $messageTemplate->account_id === $user->account_id
            && ($messageTemplate->is_public || $messageTemplate->user_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MessageTemplate $messageTemplate): bool
    {
        // Only the creator can update their template
        return $messageTemplate->account_id === $user->account_id
            && $messageTemplate->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MessageTemplate $messageTemplate): bool
    {
        // Only the creator can delete their template
        return $messageTemplate->account_id === $user->account_id
            && $messageTemplate->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MessageTemplate $messageTemplate): bool
    {
        return $this->update($user, $messageTemplate);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MessageTemplate $messageTemplate): bool
    {
        return $this->delete($user, $messageTemplate);
    }
}
