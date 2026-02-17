<?php

namespace Modules\Attention\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Attention\Enums\AttentionStatus;
use Modules\Attention\Events\AttentionStatusChanged;
use Modules\Attention\Listeners\SendAttentionStatusChangedNotification;
use Modules\Attention\Models\Attention;
use Modules\Attention\Models\AttentionDepartment;
use Tests\TestCase;

class AttentionStatusChangedListenerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_notification_to_assigned_user()
    {
        Notification::fake();

        $user = User::factory()->create();
        $attention = Attention::factory()->create([
            'assigned_user_id' => $user->id,
            'status' => AttentionStatus::RECEIVED,
        ]);

        $event = new AttentionStatusChanged(
            $attention,
            AttentionStatus::RECEIVED,
            AttentionStatus::IN_PROCESS
        );

        $listener = new SendAttentionStatusChangedNotification;
        $listener->handle($event);

        Notification::assertSentTo($user, \Modules\Attention\Notifications\AttentionStatusChangedNotification::class);
    }

    /** @test */
    public function it_sends_notification_to_department_users()
    {
        Notification::fake();

        $department = AttentionDepartment::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $department->users()->attach([$user1->id, $user2->id]);

        $attention = Attention::factory()->create([
            'department_id' => $department->id,
            'status' => AttentionStatus::RECEIVED,
        ]);

        $event = new AttentionStatusChanged(
            $attention,
            AttentionStatus::RECEIVED,
            AttentionStatus::IN_PROCESS
        );

        $listener = new SendAttentionStatusChangedNotification;
        $listener->handle($event);

        Notification::assertSentTo($user1, \Modules\Attention\Notifications\AttentionStatusChangedNotification::class);
        Notification::assertSentTo($user2, \Modules\Attention\Notifications\AttentionStatusChangedNotification::class);
    }

    /** @test */
    public function it_handles_errors_gracefully()
    {
        $attention = Attention::factory()->create([
            'assigned_user_id' => 99999, // Usuario inexistente
            'status' => AttentionStatus::RECEIVED,
        ]);

        $event = new AttentionStatusChanged(
            $attention,
            AttentionStatus::RECEIVED,
            AttentionStatus::IN_PROCESS
        );

        $listener = new SendAttentionStatusChangedNotification;

        // No debe lanzar excepción
        $listener->handle($event);

        $this->assertTrue(true); // Si llegamos aquí, el manejo de errores funcionó
    }
}
