<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskEmailActivity\Support\CanIEmailDataset;
use Tests\TestCase;

/**
 * El comando sobrescribe un fichero DEL REPO, así que cada test guarda el
 * original y lo restaura pase lo que pase: dejarlo corrupto rompería la pestaña
 * "Compatibilidad" de todo el módulo y el fallo solo se vería al abrirla.
 */
class UpdateCanIEmailDataCommandTest extends TestCase
{
    private string $backup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backup = File::get(CanIEmailDataset::path());
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        File::put(CanIEmailDataset::path(), $this->backup);
        CanIEmailDataset::flush();

        parent::tearDown();
    }

    private function validPayload(int $features = 150): array
    {
        return [
            'api_version' => '1.0.4',
            'last_update_date' => '2030-01-01 00:00:00 +0000',
            'nicenames' => ['family' => ['gmail' => 'Gmail'], 'platform' => ['ios' => 'iOS']],
            'data' => array_map(fn (int $i) => [
                'slug' => "css-test-{$i}",
                'title' => "test {$i}",
                'description' => '',
                'url' => '',
                'category' => 'css',
                'stats' => ['gmail' => ['ios' => ['2020-01' => 'y']]],
            ], range(1, $features)),
        ];
    }

    public function test_it_replaces_the_dataset_with_the_downloaded_one(): void
    {
        Http::fake(['*' => Http::response($this->validPayload())]);

        $this->artisan('helpdeskemailactivity:update-caniemail')
            ->assertSuccessful();

        CanIEmailDataset::flush();

        $this->assertSame('2030-01-01 00:00:00 +0000', CanIEmailDataset::lastUpdate());
        $this->assertNotNull(CanIEmailDataset::feature('css-test-1'));
    }

    public function test_it_refuses_a_response_that_is_not_the_dataset(): void
    {
        Http::fake(['*' => Http::response('<html>error</html>')]);

        $this->artisan('helpdeskemailactivity:update-caniemail')
            ->assertFailed();

        // Lo importante no es el código de salida: es que el fichero bueno
        // siga intacto tras un intento fallido.
        $this->assertSame($this->backup, File::get(CanIEmailDataset::path()));
    }

    public function test_it_refuses_a_truncated_dataset(): void
    {
        Http::fake(['*' => Http::response($this->validPayload(features: 3))]);

        $this->artisan('helpdeskemailactivity:update-caniemail')
            ->assertFailed();

        $this->assertSame($this->backup, File::get(CanIEmailDataset::path()));
    }

    public function test_it_reports_a_failed_download(): void
    {
        Http::fake(['*' => Http::response('', 503)]);

        $this->artisan('helpdeskemailactivity:update-caniemail')
            ->assertFailed();

        $this->assertSame($this->backup, File::get(CanIEmailDataset::path()));
    }
}
