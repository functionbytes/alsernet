<?php

namespace Modules\Reviews\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Reviews\Models\GeneratedReport;

class GeneratedReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_reports');
    }

    public function view(User $user, GeneratedReport $generatedReport): bool
    {
        return $user->id === $generatedReport->user_id || $user->can('view_all_reports');
    }

    public function create(User $user): bool
    {
        return $user->can('create_reports');
    }

    public function delete(User $user, GeneratedReport $generatedReport): bool
    {
        return $user->id === $generatedReport->user_id || $user->can('delete_all_reports');
    }
}
