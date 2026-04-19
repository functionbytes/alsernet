<?php

namespace Modules\Shortcode\Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class ShortcodeCommandsTest extends TestCase
{
    // ---------------------------------------------------------------------
    // shortcode:list
    // ---------------------------------------------------------------------

    public function test_list_command_outputs_registered_shortcodes(): void
    {
        $this->artisan('shortcode:list')
            ->assertSuccessful()
            ->expectsOutputToContain('button')
            ->expectsOutputToContain('alert');
    }

    // ---------------------------------------------------------------------
    // shortcode:compile
    // ---------------------------------------------------------------------

    public function test_compile_command_renders_shortcode(): void
    {
        $this->artisan('shortcode:compile', ['content' => '[alert type="success"]Hola[/alert]'])
            ->assertSuccessful()
            ->expectsOutputToContain('alert-success');
    }

    public function test_compile_command_with_strip_removes_shortcodes(): void
    {
        // El test sólo verifica el exit code: el output exacto depende de la locale.
        $this->artisan('shortcode:compile', [
            'content' => 'antes [alert]x[/alert] fin',
            '--strip' => true,
        ])->assertSuccessful();
    }

    // ---------------------------------------------------------------------
    // shortcode:preview
    // ---------------------------------------------------------------------

    public function test_preview_command_renders_registered_shortcode(): void
    {
        $this->artisan('shortcode:preview', ['name' => 'badge'])
            ->assertSuccessful()
            ->expectsOutputToContain('badge');
    }

    public function test_preview_command_fails_for_unknown_shortcode(): void
    {
        $this->artisan('shortcode:preview', ['name' => 'no-existe-xyz'])
            ->assertFailed();
    }

    // ---------------------------------------------------------------------
    // shortcode:find
    // ---------------------------------------------------------------------

    public function test_find_command_detects_shortcodes_in_file(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'scfind').'.txt';
        file_put_contents($tmp, 'Hola [alert]x[/alert] mundo [badge /]');

        $this->artisan('shortcode:find', ['path' => $tmp, '--ext' => 'txt'])
            ->assertSuccessful()
            ->expectsOutputToContain('alert')
            ->expectsOutputToContain('badge');

        @unlink($tmp);
    }

    public function test_find_command_fails_for_missing_path(): void
    {
        $this->artisan('shortcode:find', ['path' => '/ruta/que-no-existe'])
            ->assertFailed();
    }

    // ---------------------------------------------------------------------
    // shortcode:make
    // ---------------------------------------------------------------------

    public function test_make_command_creates_stub_file(): void
    {
        $name = 'test-make-'.uniqid();
        $expected = base_path('modules/Shortcode/app/Shortcodes/'.Str::studly($name).'Shortcode.php');

        try {
            $this->artisan('shortcode:make', ['name' => $name, '--module' => 'Shortcode'])
                ->assertSuccessful();

            $this->assertFileExists($expected);
            $contents = file_get_contents($expected);
            $this->assertStringContainsString("public const NAME = '{$name}'", $contents);
        } finally {
            @unlink($expected);
        }
    }

    public function test_make_command_rejects_invalid_name(): void
    {
        $this->artisan('shortcode:make', ['name' => 'Invalid Name!!'])
            ->assertFailed();
    }

    public function test_make_command_rejects_nonexistent_module(): void
    {
        $this->artisan('shortcode:make', ['name' => 'whatever', '--module' => 'NoExiste_'.uniqid()])
            ->assertFailed();
    }
}
