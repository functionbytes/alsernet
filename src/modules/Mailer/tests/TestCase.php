<?php

namespace Modules\Mailer\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bypass the 'settings' role-middleware since the SQLite test DB
        // uses a stripped-down roles migration without guard_name.
        $this->withoutMiddleware(RoleMiddleware::class);
        // Allow all policy checks so tests focus on business logic.
        Gate::before(fn () => true);
    }

    /**
     * Create a user with optional named permissions.
     */
    protected function createUser(array $permissions = []): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /**
     * Create a lang record needed for FK constraints and return its id.
     */
    protected function createLang(array $attributes = []): int
    {
        return DB::table('langs')->insertGetId(array_merge([
            'uid' => Str::uuid(),
            'title' => 'Español',
            'iso_code' => 'es_'.uniqid(),
            'lenguage_code' => 'es-'.uniqid(),
            'locate' => 'es_CO',
            'date_format_full' => 'l, d F Y',
            'date_format_lite' => 'd/m/Y',
            'available' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * Create a MailerTemplate with a MailerTemplateLang translation.
     */
    protected function createTemplate(int $langId, array $attributes = []): MailerTemplate
    {
        $template = MailerTemplate::create(array_merge([
            'key' => 'test_template_'.uniqid(),
            'name' => 'Template de prueba',
            'module' => 'core',
            'is_enabled' => true,
            'is_protected' => false,
        ], $attributes));

        MailerTemplateLang::create([
            'mailer_template_id' => $template->id,
            'lang_id' => $langId,
            'subject' => 'Asunto de prueba',
            'preheader' => null,
            'content' => '<p>Contenido de prueba</p>',
        ]);

        return $template->fresh();
    }
}
