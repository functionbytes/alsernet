<?php

namespace Modules\Mailrelay\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Mailrelay\Entities\ImportJob;
use Modules\Mailrelay\Enums\ImportStatus;

class ImportPolicy
{
    use HandlesAuthorization, HasSafePermissionCheck;

    /**
     * Determine if the user can view any import jobs.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.imports.view');
    }

    /**
     * Determine if the user can view the import job.
     */
    public function view(User $user, ImportJob $importJob): bool
    {
        return $this->hasPermission($user, 'mailrelay.imports.view');
    }

    /**
     * Determine if the user can create import jobs.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'mailrelay.imports.create');
    }

    /**
     * Determine if the user can delete the import job.
     */
    public function delete(User $user, ImportJob $importJob): bool
    {
        // Cannot delete processing imports
        if ($importJob->status === ImportStatus::PROCESSING) {
            return false;
        }

        return $this->hasPermission($user, 'mailrelay.imports.delete');
    }
}
