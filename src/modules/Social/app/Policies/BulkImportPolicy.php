<?php

namespace Modules\Social\Policies;

use App\Models\User;
use Modules\Social\Models\BulkImport;

class BulkImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('social.bulk-import');
    }

    public function view(User $user, BulkImport $bulkImport): bool
    {
        return $user->account_id === $bulkImport->account_id && $user->can('social.bulk-import');
    }

    public function create(User $user): bool
    {
        return $user->can('social.bulk-import');
    }

    public function update(User $user, BulkImport $bulkImport): bool
    {
        return $user->account_id === $bulkImport->account_id && $user->can('social.bulk-import');
    }

    public function delete(User $user, BulkImport $bulkImport): bool
    {
        return $user->account_id === $bulkImport->account_id && $user->can('social.bulk-import');
    }
}
