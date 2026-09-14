<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Rules\PasswordNotReused;
use Modules\Auth\Rules\StrongPassword;
use Modules\Auth\Services\PasswordHistoryService;
use Modules\Auth\Tests\AuthTestCase;

class PasswordPolicyTest extends AuthTestCase
{
    public function test_strong_password_rejects_short_password(): void
    {
        $rule = new StrongPassword;
        $errors = [];

        $rule->validate('password', 'abc', function ($msg) use (&$errors) {
            $errors[] = $msg;
        });

        $this->assertNotEmpty($errors);
    }

    public function test_strong_password_requires_uppercase(): void
    {
        config()->set('auth.auth-policy.password', [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
        ]);

        $rule = new StrongPassword;
        $errors = [];

        $rule->validate('password', 'abcdefg1!', function ($msg) use (&$errors) {
            $errors[] = $msg;
        });

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('mayúscula', $errors[0]);
    }

    public function test_strong_password_accepts_valid_password(): void
    {
        config()->set('auth.auth-policy.password', [
            'min_length' => 10,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
        ]);

        $rule = new StrongPassword;
        $errors = [];

        $rule->validate('password', 'Str0ng!Pass', function ($msg) use (&$errors) {
            $errors[] = $msg;
        });

        $this->assertEmpty($errors);
    }

    public function test_password_not_reused_blocks_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('CurrentP@ss1')]);
        $rule = new PasswordNotReused($user);

        $errors = [];
        $rule->validate('password', 'CurrentP@ss1', function ($msg) use (&$errors) {
            $errors[] = $msg;
        });

        $this->assertNotEmpty($errors);
    }

    public function test_password_history_records_and_detects_reuse(): void
    {
        config()->set('auth.auth-policy.password.history_count', 3);
        $user = User::factory()->create(['password' => Hash::make('Initial@123')]);
        $service = app(PasswordHistoryService::class);

        $service->record($user, Hash::make('Old@Pass1'));
        $service->record($user, Hash::make('Old@Pass2'));

        $this->assertTrue($service->isReused($user, 'Old@Pass1'));
        $this->assertTrue($service->isReused($user, 'Old@Pass2'));
        $this->assertFalse($service->isReused($user, 'Brand@New9'));
    }
}
