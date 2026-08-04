<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\ConversationGreeting;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConversationGreetingsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // Ver nota en OffHoursResponsesControllerTest::setUp().
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        // Ver nota en OffHoursResponsesControllerTest::setUp().
        ConversationGreeting::query()->delete();
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('settings.helpdesk.business.greeting'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.business.greeting'))
            ->assertForbidden();
    }

    public function test_manager_can_view_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.business.greeting'))
            ->assertOk();
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_manager_can_create_greeting(): void
    {
        $payload = [
            'channel' => 'web',
            'language' => 'en',
            'message' => 'Hi! Thanks for reaching out.',
            'is_active' => '1',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.conversation-greetings.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.business.greeting'));

        $this->assertDatabaseHas('helpdesk_conversation_greetings', [
            'channel' => 'web',
            'language' => 'en',
            'message' => 'Hi! Thanks for reaching out.',
            'is_active' => true,
        ], 'helpdesk');
    }

    public function test_store_fails_without_message(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.conversation-greetings.store'), [])
            ->assertSessionHasErrors(['message'], null, 'greeting');
    }

    public function test_store_fails_with_invalid_channel(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.conversation-greetings.store'), [
                'channel' => 'telegram',
                'message' => 'Mensaje',
            ])
            ->assertSessionHasErrors(['channel'], null, 'greeting');
    }

    public function test_store_fails_for_duplicate_channel_language_combination(): void
    {
        ConversationGreeting::query()->create([
            'channel' => null,
            'language' => null,
            'message' => 'Bienvenida existente',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.conversation-greetings.store'), [
                'message' => 'Duplicado',
            ])
            ->assertRedirect(route('settings.helpdesk.business.greeting'))
            ->assertSessionHas('error');

        $this->assertSame(1, ConversationGreeting::query()->whereNull('channel')->whereNull('language')->count());
    }

    public function test_store_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.conversation-greetings.store'), [
                'message' => 'Mensaje',
            ])
            ->assertForbidden();
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_manager_can_update_greeting(): void
    {
        $greeting = ConversationGreeting::query()->create([
            'message' => 'Original',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.conversation-greetings.update', $greeting), [
                'channel' => 'instagram',
                'language' => 'de',
                'message' => 'Aktualisiert',
                'is_active' => '0',
            ])
            ->assertRedirect(route('settings.helpdesk.business.greeting'));

        $greeting->refresh();
        $this->assertSame('instagram', $greeting->channel);
        $this->assertSame('de', $greeting->language);
        $this->assertSame('Aktualisiert', $greeting->message);
        $this->assertFalse($greeting->is_active);
    }

    public function test_update_fails_for_user_without_permission(): void
    {
        $greeting = ConversationGreeting::query()->create([
            'message' => 'Protegido',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.conversation-greetings.update', $greeting), [
                'message' => 'Modificado',
            ])
            ->assertForbidden();
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_manager_can_delete_greeting(): void
    {
        $greeting = ConversationGreeting::query()->create([
            'message' => 'A eliminar',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.conversation-greetings.destroy', $greeting))
            ->assertRedirect(route('settings.helpdesk.business.greeting'));

        $this->assertDatabaseMissing('helpdesk_conversation_greetings', ['id' => $greeting->id], 'helpdesk');
    }

    public function test_destroy_fails_for_user_without_permission(): void
    {
        $greeting = ConversationGreeting::query()->create([
            'message' => 'No eliminar',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.conversation-greetings.destroy', $greeting))
            ->assertForbidden();
    }
}
