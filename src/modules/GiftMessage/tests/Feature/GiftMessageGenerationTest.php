<?php

namespace Modules\GiftMessage\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\GiftMessage\Database\Seeders\GiftMessagePermissionsSeeder;
use Modules\GiftMessage\Models\GiftMessageConfig;
use Modules\GiftMessage\Models\GiftMessageGeneration;
use Tests\TestCase;

class GiftMessageGenerationTest extends TestCase
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
        // row here so the config set up in each test is the same one the controller/service
        // sees when it calls current() during the request.
        GiftMessageConfig::query()->forceCreate(['id' => 1]);
    }

    public function test_generating_envelope_pdf_records_it_in_history(): void
    {
        Storage::fake('public');

        $backgroundPath = UploadedFile::fake()->image('envelope-bg.jpg')->store('giftmessage/images', 'public');
        GiftMessageConfig::current()->update(['envelope_image' => $backgroundPath]);

        $response = $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'envelope',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Feliz cumpleanos',
                    'firstname' => 'Maria',
                    'lastname' => 'Garcia',
                    'id_gestion' => '73230',
                ]],
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('gift_message_generations', [
            'type' => 'envelope',
            'rows_count' => 1,
        ]);

        $generation = GiftMessageGeneration::query()->latest()->first();
        Storage::disk('public')->assertExists($generation->file_path);
    }

    public function test_generating_card_pdf_with_emoji_message_does_not_fail(): void
    {
        Storage::fake('public');
        Http::fake(['*' => Http::response(str_repeat('x', 200), 200)]);

        $response = $this->actingAs($this->admin)
            ->post(route('giftmessage.generate'), [
                'type' => 'card',
                'rows' => [[
                    'id_order' => 1,
                    'gift_message' => 'Feliz cumple 🎂',
                    'firstname' => 'Juan',
                    'lastname' => 'Perez',
                    'id_gestion' => '73231',
                ]],
            ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_history_index_lists_generations(): void
    {
        GiftMessageGeneration::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('giftmessage.history.index'))
            ->assertOk();
    }

    public function test_history_index_filters_by_type(): void
    {
        GiftMessageGeneration::factory()->count(2)->envelope()->create();
        GiftMessageGeneration::factory()->card()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('giftmessage.history.index', ['type' => 'card']));

        $response->assertOk();
        $response->assertViewHas('generations', fn ($generations) => $generations->total() === 1);
    }

    public function test_download_serves_the_stored_pdf(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('giftmessage/generated/test.pdf', '%PDF-1.7 fake content');

        $generation = GiftMessageGeneration::factory()->create([
            'file_path' => 'giftmessage/generated/test.pdf',
            'file_name' => 'test.pdf',
        ]);

        $this->actingAs($this->admin)
            ->get(route('giftmessage.history.download', $generation))
            ->assertOk();
    }

    public function test_destroy_removes_row_and_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('giftmessage/generated/test.pdf', 'fake content');

        $generation = GiftMessageGeneration::factory()->create([
            'file_path' => 'giftmessage/generated/test.pdf',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('giftmessage.history.destroy', $generation))
            ->assertRedirect(route('giftmessage.history.index'));

        $this->assertDatabaseMissing('gift_message_generations', ['id' => $generation->id]);
        Storage::disk('public')->assertMissing('giftmessage/generated/test.pdf');
    }

    public function test_bulk_delete_removes_multiple_generations(): void
    {
        Storage::fake('public');

        $generations = GiftMessageGeneration::factory()->count(2)->create();
        foreach ($generations as $generation) {
            Storage::disk('public')->put($generation->file_path, 'fake content');
        }

        $this->actingAs($this->admin)
            ->postJson(route('giftmessage.history.bulk-action'), [
                'action' => 'delete',
                'ids' => $generations->pluck('id')->toArray(),
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('gift_message_generations', 0);
    }

    public function test_user_without_permission_cannot_view_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('giftmessage.history.index'))
            ->assertForbidden();
    }

    public function test_prune_command_deletes_only_old_generations(): void
    {
        Storage::fake('public');

        $old = GiftMessageGeneration::factory()->create(['file_path' => 'giftmessage/generated/old.pdf']);
        $old->created_at = now()->subDays(100);
        $old->save();
        Storage::disk('public')->put($old->file_path, 'old content');

        $recent = GiftMessageGeneration::factory()->create(['file_path' => 'giftmessage/generated/recent.pdf']);
        Storage::disk('public')->put($recent->file_path, 'recent content');

        $this->artisan('giftmessage:prune-generations')->assertSuccessful();

        $this->assertDatabaseMissing('gift_message_generations', ['id' => $old->id]);
        $this->assertDatabaseHas('gift_message_generations', ['id' => $recent->id]);
        Storage::disk('public')->assertMissing($old->file_path);
        Storage::disk('public')->assertExists($recent->file_path);
    }
}
