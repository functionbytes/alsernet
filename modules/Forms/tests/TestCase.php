<?php

namespace Modules\Forms\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormSubmission;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase as BaseTestCase;

/**
 * En el proyecto de origen esto usaba RefreshDatabase. Aquí NO: la BD de test
 * es la misma que la de desarrollo (phpunit fuerza sqlite, pero las conexiones
 * cualificadas a mano y las variables de entorno del contenedor ganan a ese
 * force), así que un RefreshDatabase vacía datos reales. El patrón del
 * proyecto es DatabaseTransactions declarando las conexiones a revertir.
 *
 * 'mysql' es la conexión por defecto (donde viven las tablas de Forms);
 * 'mariadb' es la que usa assertDatabaseHas si no se le pasa una explícita.
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql', 'mariadb'];

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass the 'settings' role middleware — the roles table in the test
        // environment lacks name/guard_name columns (custom slim migration).
        $this->withoutMiddleware(RoleMiddleware::class);
    }

    protected function createUser(array $permissions = []): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    protected function createForm(array $attributes = []): Form
    {
        return Form::factory()->create($attributes);
    }

    protected function createActiveForm(array $attributes = []): Form
    {
        return Form::factory()->active()->create($attributes);
    }

    protected function createField(Form $form, array $attributes = []): FormField
    {
        return FormField::factory()
            ->for($form)
            ->create($attributes);
    }

    protected function createSubmission(Form $form, array $attributes = []): FormSubmission
    {
        return FormSubmission::factory()
            ->for($form)
            ->create($attributes);
    }
}
