<?php

namespace Modules\HelpdeskChatFlow\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Modules\HelpdeskChatFlow\Database\Seeders\ChatFlowPermissionsSeeder;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Tests\TestCase;
use Spatie\Permission\Models\Role;

/**
 * La subida del simulador solo admite los tipos del <input accept> del editor y
 * guarda el archivo con la extensión deducida del contenido, no con la que
 * manda el navegador.
 */
class SimulatorUploadTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $user;

    private ChatFlow $flow;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            \DB::connection('helpdesk')->statement('SELECT 1 FROM helpdesk_chat_flows LIMIT 1');
        } catch (\Throwable) {
            $this->markTestSkipped('helpdesk_chat_flows table not available in test DB.');
        }

        $this->seed(ChatFlowPermissionsSeeder::class);
        Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('super-settings');
        $this->user->givePermissionTo(['chatflow.view', 'chatflow.update']);

        $this->flow = ChatFlow::factory()->create();

        Storage::fake('local');
    }

    private function upload(UploadedFile $file): TestResponse
    {
        return $this->actingAs($this->user)->postJson(route('chatflow.test.upload', $this->flow), [
            'session_key' => 'sess_1',
            'doc_key' => 'dni',
            'file' => $file,
        ]);
    }

    public function test_rejects_disallowed_file_types(): void
    {
        $this->upload(UploadedFile::fake()->create('shell.php', 1, 'application/x-php'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertSame([], Storage::disk('local')->allFiles('chatflow-test'));
    }

    public function test_accepts_allowed_types_and_stores_with_content_extension(): void
    {
        // Nombre con extensión engañosa: se guarda según el contenido real (PNG).
        $this->upload(UploadedFile::fake()->create('dni.exe', 1, 'image/png'))
            ->assertJsonMissingValidationErrors('file');

        $stored = Storage::disk('local')->allFiles('chatflow-test/sess_1');
        $this->assertCount(1, $stored);
        $this->assertStringEndsWith('.png', $stored[0]);
    }
}
