---
globs: "modules/*/app/Policies/**/*.php"
---

# Policy Rules

## Structure Required

```php
<?php

namespace Modules\{ModuleName}\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\{ModuleName}\Models\{Entity};

class {Entity}Policy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any resources.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('{alias}.view');
    }

    /**
     * Determine whether the user can view the resource.
     */
    public function view(User $user, {Entity} $entity): bool
    {
        return $user->can('{alias}.view')
            && $this->belongsToUser($user, $entity);
    }

    /**
     * Determine whether the user can create resources.
     */
    public function create(User $user): bool
    {
        return $user->can('{alias}.create');
    }

    /**
     * Determine whether the user can update the resource.
     */
    public function update(User $user, {Entity} $entity): bool
    {
        return $user->can('{alias}.update')
            && $this->belongsToUser($user, $entity);
    }

    /**
     * Determine whether the user can delete the resource.
     */
    public function delete(User $user, {Entity} $entity): bool
    {
        return $user->can('{alias}.delete');
    }

    /**
     * Determine whether the user can manage all resources.
     */
    public function manage(User $user): bool
    {
        return $user->can('{alias}.manage');
    }

    /**
     * Helper: check if resource belongs to user
     */
    protected function belongsToUser(User $user, {Entity} $entity): bool
    {
        if ($user->can('{alias}.manage')) {
            return true;
        }

        return $entity->user_id === $user->id
            || $entity->assigned_user_id === $user->id;
    }
}
```

## Registro en ServiceProvider

```php
// En {ModuleName}ServiceProvider::boot()
use Illuminate\Support\Facades\Gate;
use Modules\{ModuleName}\Models\{Entity};
use Modules\{ModuleName}\Policies\{Entity}Policy;

protected function registerPolicies(): void
{
    Gate::policy({Entity}::class, {Entity}Policy::class);
}
```

Llamar `$this->registerPolicies();` en el `boot()` despues de `registerRoutes()`.

## Uso en controllers

```php
public function edit({Entity} $entity): View
{
    $this->authorize('update', $entity);
    return view('{alias}::entity.form', compact('entity'));
}

public function destroy({Entity} $entity): RedirectResponse
{
    $this->authorize('delete', $entity);
    $entity->delete();
    return redirect()->route('{alias}.index')->with('success', 'Eliminado');
}

// Para acciones generales
public function index(): View
{
    $this->authorize('viewAny', {Entity}::class);
    // ...
}
```

## Reglas

- **Naming**: `{Entity}Policy` (e.g., `PostPolicy`, `AttentionPolicy`)
- **Metodos estandar**: `viewAny`, `view`, `create`, `update`, `delete`, `manage`
- **Metodos custom**: agregar segun necesidad (`assign`, `resolve`, `approve`, etc.)
- **Spatie integration**: SIEMPRE usar `$user->can('{alias}.action')` con convencion de permisos
- **Ownership check**: combinar permiso + `belongsToUser()` helper
- **Manage permission**: super-permiso que bypassa ownership checks
- **Return type**: SIEMPRE `bool` (Laravel 11+ type declarations)
- **User type hint**: `App\Models\User` (SIEMPRE)

## Authorization en routes (alternativa)

```php
Route::get('/{id}', [Controller::class, 'show'])
    ->middleware('can:view,entity')
    ->name('show');
```

## Authorization antes de query

```php
// En controller que lista solo los del usuario
public function index(): View
{
    $this->authorize('viewAny', Post::class);

    $posts = Post::query()
        ->when(! auth()->user()->can('blog.view-all'), function ($q) {
            $q->where('user_id', auth()->id());
        })
        ->paginate(15);

    return view('blog::posts.index', compact('posts'));
}
```

## NO usar

- `Gate::define()` inline (preferir Policy class)
- Policies sin Spatie permission check
- Acceso directo a base de datos para ownership (usar relacion del modelo)
- Skip `authorize()` en controllers (siempre verificar)

## Ver tambien

- [rules/controllers.md] para uso de `authorize()` en controllers
- [rules/seeders.md] para crear permisos
- [rules/models.md] para relaciones user_id/assigned_user_id
