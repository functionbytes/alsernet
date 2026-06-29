<?php

declare(strict_types=1);

namespace Modules\Captcha\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('captcha.general.math-captcha', [
            'operands' => ['+', '-', '*'],
            'rand-min' => 2,
            'rand-max' => 5,
        ]);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        return tap(User::factory()->create(), fn (User $user) => $user->assignRole($role));
    }
}
