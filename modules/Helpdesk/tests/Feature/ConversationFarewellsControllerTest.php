<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Http\Middleware\VerifyCsrfToken;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Models\ConversationFarewell;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConversationFarewellsControllerTest extends TestCase
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
        ConversationFarewell::query()->delete();
    }

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('settings.helpdesk.business.farewell'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_user_without_permission_cannot_access_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.helpdesk.business.farewell'))
            ->assertForbidden();
    }

    public function test_manager_can_view_index(): void
    {
        $this->actingAs($this->manager)
            ->get(route('settings.helpdesk.business.farewell'))
            ->assertOk();
    }

    // ─── store ────────────────────────────────────────────────────────────────

    public function test_manager_can_create_farewell(): void
    {
        $payload = [
            'channel' => 'web',
            'language' => 'en',
            'message' => 'Thanks for contacting us. Have a great day!',
            'is_active' => '1',
        ];

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.conversation-farewells.store'), $payload)
            ->assertRedirect(route('settings.helpdesk.business.farewell'));

        $this->assertDatabaseHas('helpdesk_conversation_farewells', [
            'channel' => 'web',
            'language' => 'en',
            'message' => 'Thanks for contacting us. Have a great day!',
            'is_active' => true,
        ], 'helpdesk');
    }

    public function test_store_fails_without_message(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.conversation-farewells.store'), [])
            ->assertSessionHasErrors(['message'], null, 'farewell');
    }

    public function test_store_fails_with_invalid_language(): void
    {
        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.conversation-farewells.store'), [
                'language' => 'zz',
                'message' => 'Mensaje',
            ])
            ->assertSessionHasErrors(['language'], null, 'farewell');
    }

    public function test_store_fails_for_duplicate_channel_language_combination(): void
    {
        ConversationFarewell::query()->create([
            'channel' => null,
            'language' => null,
            'message' => 'Despedida existente',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->post(route('settings.helpdesk.conversation-farewells.store'), [
                'message' => 'Duplicado',
            ])
            ->assertRedirect(route('settings.helpdesk.business.farewell'))
            ->assertSessionHas('error');

        $this->assertSame(1, ConversationFarewell::query()->whereNull('channel')->whereNull('language')->count());
    }

    public function test_store_fails_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.helpdesk.conversation-farewells.store'), [
                'message' => 'Mensaje',
            ])
            ->assertForbidden();
    }

    // ─── update ───────────────────────────────────────────────────────────────

    public function test_manager_can_update_farewell(): void
    {
        $farewell = ConversationFarewell::query()->create([
            'message' => 'Original',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->put(route('settings.helpdesk.conversation-farewells.update', $farewell), [
                'channel' => 'facebook',
                'language' => 'it',
                'message' => 'Aggiornato',
                'is_active' => '0',
            ])
            ->assertRedirect(route('settings.helpdesk.business.farewell'));

        $farewell->refresh();
        $this->assertSame('facebook', $farewell->channel);
        $this->assertSame('it', $farewell->language);
        $this->assertSame('Aggiornato', $farewell->message);
        $this->assertFalse($farewell->is_active);
    }

    public function test_update_fails_for_user_without_permission(): void
    {
        $farewell = ConversationFarewell::query()->create([
            'message' => 'Protegido',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('settings.helpdesk.conversation-farewells.update', $farewell), [
                'message' => 'Modificado',
            ])
            ->assertForbidden();
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_manager_can_delete_farewell(): void
    {
        $farewell = ConversationFarewell::query()->create([
            'message' => 'A eliminar',
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->delete(route('settings.helpdesk.conversation-farewells.destroy', $farewell))
            ->assertRedirect(route('settings.helpdesk.business.farewell'));

        $this->assertDatabaseMissing('helpdesk_conversation_farewells', ['id' => $farewell->id], 'helpdesk');
    }

    public function test_destroy_fails_for_user_without_permission(): void
    {
        $farewell = ConversationFarewell::query()->create([
            'message' => 'No eliminar',
            'is_active' => true,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('settings.helpdesk.conversation-farewells.destroy', $farewell))
            ->assertForbidden();
    }
}
