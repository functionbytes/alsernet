<?php

namespace Modules\Campaign\Policies;

use App\Models\User;
use Modules\Campaign\Models\Template\PageTemplate;

/**
 * Autorización de PageTemplate. Mapea a los permisos del módulo Role
 * (campaigns.templates.view / campaigns.templates.manage) cuando el sistema de
 * permisos está disponible; si no, permite a usuarios autenticados (las rutas
 * del manager ya están protegidas por middleware role:super-admin|super-settings).
 */
class PageTemplatePolicy
{
    private function allows(User $user, string $permission): bool
    {
        // spatie/laravel-permission expone can()/hasPermissionTo() en el User.
        if (method_exists($user, 'hasPermissionTo')) {
            try {
                return $user->hasPermissionTo($permission);
            } catch (\Throwable $e) {
                // Permiso no registrado → caer al fallback permisivo.
                return true;
            }
        }

        return true;
    }

    public function read(User $user): bool
    {
        return $this->allows($user, 'campaigns.templates.view');
    }

    public function preview(User $user, $subject = null): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'campaigns.templates.manage');
    }

    public function update(User $user, $subject = null): bool
    {
        return $this->allows($user, 'campaigns.templates.manage');
    }

    public function copy(User $user, $subject = null): bool
    {
        return $this->allows($user, 'campaigns.templates.manage');
    }

    public function delete(User $user, $subject = null): bool
    {
        return $this->allows($user, 'campaigns.templates.manage');
    }
}
