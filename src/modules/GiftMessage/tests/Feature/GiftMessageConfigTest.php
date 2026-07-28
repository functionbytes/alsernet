<?php

namespace Modules\GiftMessage\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\GiftMessage\Database\Seeders\GiftMessagePermissionsSeeder;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Services\GiftMessageOrderService;
use Tests\TestCase;

class GiftMessageConfigTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GiftMessagePermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'giftmessage.view',
            'giftmessage.create',
            'giftmessage.update',
            'giftmessage.delete',
        ]);

        // GiftMessageConfig::current() looks up id=1 via firstOrCreate(), but 'id' is not
        // fillable, so a missing row would otherwise be created with an autoincremented id
        // instead of 1, breaking the singleton lookup on the next current() call. Force the
        // row here so every current() call in the test resolves to the same instance.
        GiftMessageConfig::query()->forceCreate(['id' => 1]);
    }

    public function test_index_shows_the_panel_with_current_config(): void
    {
        $this->mock(GiftMessageOrderService::class, function ($mock) {
            $mock->shouldReceive('ordersWithGiftMessage')->once()->andReturn([]);
        });

        $this->actingAs($this->admin)
            ->get(route('giftmessage.index'))
            ->assertOk();
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('giftmessage.index'))
            ->assertForbidden();
    }

    public function test_uploading_images_updates_the_config(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('giftmessage.images.store'), [
                'envelope_image' => UploadedFile::fake()->image('envelope.jpg'),
                'card_image' => UploadedFile::fake()->image('card.jpg'),
            ])
            ->assertRedirect(route('giftmessage.index'));

        $config = GiftMessageConfig::current()->fresh();

        $this->assertStringStartsWith('giftmessage/images/envelope_', $config->envelope_image);
        $this->assertStringStartsWith('giftmessage/images/card_', $config->card_image);

        $this->assertDatabaseHas('gift_message_configs', [
            'id' => $config->id,
            'envelope_image' => $config->envelope_image,
            'card_image' => $config->card_image,
        ]);

        Storage::disk('public')->assertExists($config->envelope_image);
        Storage::disk('public')->assertExists($config->card_image);
    }

    public function test_saving_fonts_updates_the_config(): void
    {
        $this->actingAs($this->admin)
            ->post(route('giftmessage.fonts.update'), [
                'env_t1_font' => 'times',
                'env_t1_size' => 16,
                'env_t2_font' => 'courier',
                'env_t2_size' => 10,
                'card_t1_font' => 'dejavusans',
                'card_t1_size' => 18,
                'card_t2_font' => 'dejavuserif',
                'card_t2_size' => 9,
            ])
            ->assertRedirect(route('giftmessage.index'));

        $this->assertDatabaseHas('gift_message_configs', [
            'env_t1_font' => 'times',
            'env_t1_size' => 16,
            'env_t2_font' => 'courier',
            'env_t2_size' => 10,
            'card_t1_font' => 'dejavusans',
            'card_t1_size' => 18,
            'card_t2_font' => 'dejavuserif',
            'card_t2_size' => 9,
        ]);
    }

    public function test_saving_positions_updates_the_config_via_ajax(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.positions.save'), [
                'scope' => 'envelope',
                't1_x' => 15.5,
                't1_y' => 20.25,
                't2_x' => 30,
                't2_y' => 40,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('gift_message_configs', [
            'env_t1_x' => 15.5,
            'env_t1_y' => 20.25,
            'env_t2_x' => 30,
            'env_t2_y' => 40,
        ]);
    }

    public function test_saving_positions_rejects_invalid_scope(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.positions.save'), [
                'scope' => 'invalid',
                't1_x' => 10,
                't1_y' => 10,
                't2_x' => 10,
                't2_y' => 10,
            ])
            ->assertStatus(422);
    }

    public function test_user_without_update_permission_cannot_upload_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->givePermissionTo('giftmessage.view');

        $this->actingAs($user)
            ->post(route('giftmessage.images.store'), [
                'envelope_image' => UploadedFile::fake()->image('envelope.jpg'),
            ])
            ->assertForbidden();
    }
}
