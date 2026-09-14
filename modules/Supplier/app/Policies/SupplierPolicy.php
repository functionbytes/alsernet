<?php

namespace Modules\Supplier\Policies;

use App\Models\User;
use Modules\Supplier\Models\Supplier\Supplier;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.view');
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.configure');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->edit($user, $supplier);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.configure');
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $this->delete($user, $supplier);
    }

    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $user->hasRole('super-admin');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.view');
    }

    public function edit(User $user, Supplier $supplier): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.edit');
    }

    public function toggle(User $user, Supplier $supplier): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.toggle');
    }

    public function manageSources(User $user, Supplier $supplier): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.sources.manage');
    }

    public function manageCategories(User $user, Supplier $supplier): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.categories.manage');
    }

    public function syncErp(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.sync.erp');
    }

    public function triggerSync(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.sync.trigger');
    }

    public function configure(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.configure');
    }

    public function import(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.import');
    }

    public function manageContent(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.content.manage');
    }

    public function manageAutomation(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.automation.manage');
    }

    public function manageSyncConfig(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('suppliers.sync.config');
    }
}
