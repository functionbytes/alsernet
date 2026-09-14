<?php

namespace Modules\Auth\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Jobs\DeleteUserAccountJob;
use Modules\Auth\Tests\AuthTestCase;

class AccountDeletionTest extends AuthTestCase
{
    public function test_delete_requires_typed_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('pwd'),
            'available' => true,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('settings.auth.account.delete'), [
                'password' => 'pwd',
                'confirmation' => 'wrong',
            ])
            ->assertStatus(422);
    }

    public function test_delete_requires_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('pwd'),
            'available' => true,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('settings.auth.account.delete'), [
                'password' => 'wrong',
                'confirmation' => 'ELIMINAR MI CUENTA',
            ])
            ->assertStatus(422);
    }

    public function test_delete_dispatches_job_and_logs_out(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'password' => Hash::make('pwd'),
            'available' => true,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('settings.auth.account.delete'), [
                'password' => 'pwd',
                'confirmation' => 'ELIMINAR MI CUENTA',
            ])
            ->assertOk();

        Queue::assertPushed(DeleteUserAccountJob::class, fn ($job) => $job->delay !== null);
        $this->assertGuest();
    }

    public function test_job_soft_deletes_user_and_revokes_credentials(): void
    {
        $user = User::factory()->create(['available' => true]);
        $user->createToken('test-token');

        (new DeleteUserAccountJob($user->id))->handle();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
