<?php

namespace Modules\Forms\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormField;
use Modules\Forms\Models\FormSubmission;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

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
