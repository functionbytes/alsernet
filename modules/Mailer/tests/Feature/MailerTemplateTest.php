<?php

namespace Modules\Mailer\Tests\Feature;

use Modules\Mailer\Models\MailerTemplateVersion;
use Modules\Mailer\Tests\TestCase;

class MailerTemplateTest extends TestCase
{
    // -------------------------------------------------------------------------
    // GET /mailers/templates — index
    // -------------------------------------------------------------------------

    public function test_template_list_requires_auth(): void
    {
        $this->get(route('mailers.templates.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_admin_can_view_template_list(): void
    {
        $user = $this->createUser(['mailer.templates.view']);

        $this->actingAs($user)
            ->get(route('mailers.templates.index'))
            ->assertOk()
            ->assertViewIs('mailer::templates.index');
    }

    // -------------------------------------------------------------------------
    // POST /mailers/templates — store
    // -------------------------------------------------------------------------

    public function test_can_create_template(): void
    {
        $user = $this->createUser(['mailer.templates.create']);
        $langId = $this->createLang();

        $this->actingAs($user)
            ->post(route('mailers.templates.store'), [
                'key' => 'nueva_plantilla_test',
                'name' => 'Nueva plantilla de prueba',
                'subject' => 'Asunto de prueba',
                'content' => '<p>Contenido HTML</p>',
                'module' => 'core',
                'lang_id' => $langId,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mailer_templates', ['key' => 'nueva_plantilla_test']);
    }

    public function test_template_requires_subject(): void
    {
        $user = $this->createUser(['mailer.templates.create']);
        $langId = $this->createLang();

        $this->actingAs($user)
            ->post(route('mailers.templates.store'), [
                'key' => 'plantilla_sin_asunto',
                'name' => 'Sin asunto',
                'content' => '<p>Contenido</p>',
                'module' => 'core',
                'lang_id' => $langId,
            ])
            ->assertSessionHasErrors(['subject']);

        $this->assertDatabaseMissing('mailer_templates', ['key' => 'plantilla_sin_asunto']);
    }

    // -------------------------------------------------------------------------
    // PATCH /mailers/templates/{uid} — update
    // -------------------------------------------------------------------------

    public function test_can_update_template(): void
    {
        $user = $this->createUser(['mailer.templates.update']);
        $langId = $this->createLang();
        $template = $this->createTemplate($langId);

        $this->actingAs($user)
            ->patch(route('mailers.templates.update', ['uid' => $template->uid]), [
                'subject' => 'Asunto actualizado',
                'content' => '<p>Contenido actualizado</p>',
                'lang_id' => $langId,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mailer_template_langs', [
            'mailer_template_id' => $template->id,
            'subject' => 'Asunto actualizado',
        ]);
    }

    public function test_update_creates_version_snapshot(): void
    {
        $user = $this->createUser(['mailer.templates.update']);
        $langId = $this->createLang();
        $template = $this->createTemplate($langId);

        $versionsBefore = MailerTemplateVersion::where('mailer_template_id', $template->id)->count();

        $this->actingAs($user)
            ->patch(route('mailers.templates.update', ['uid' => $template->uid]), [
                'subject' => 'Asunto con version nueva',
                'content' => '<p>Contenido con version nueva</p>',
                'lang_id' => $langId,
            ]);

        $versionsAfter = MailerTemplateVersion::where('mailer_template_id', $template->id)->count();

        $this->assertGreaterThan($versionsBefore, $versionsAfter);
    }

    // -------------------------------------------------------------------------
    // GET /mailers/templates/{uid}/versions — version history
    // -------------------------------------------------------------------------

    public function test_can_view_version_history(): void
    {
        $user = $this->createUser(['mailer.templates.update']);
        $langId = $this->createLang();
        $template = $this->createTemplate($langId);

        $this->actingAs($user)
            ->get(route('mailers.templates.versions', ['uid' => $template->uid]))
            ->assertOk();
    }

    public function test_version_history_requires_auth(): void
    {
        $langId = $this->createLang();
        $template = $this->createTemplate($langId);

        $this->get(route('mailers.templates.versions', ['uid' => $template->uid]))
            ->assertRedirect(route('auth.login'));
    }

    // -------------------------------------------------------------------------
    // POST /mailers/templates/{uid}/versions/{version}/restore — restore
    // -------------------------------------------------------------------------

    public function test_can_restore_version(): void
    {
        $user = $this->createUser(['mailer.templates.update']);
        $langId = $this->createLang();
        $template = $this->createTemplate($langId);

        // Create a version record with specific content
        $version = MailerTemplateVersion::create([
            'mailer_template_id' => $template->id,
            'lang_id' => $langId,
            'created_by' => $user->id,
            'subject' => 'Asunto de version antigua',
            'content' => '<p>Contenido de version antigua</p>',
        ]);

        $this->actingAs($user)
            ->post(route('mailers.templates.versions.restore', [
                'uid' => $template->uid,
                'version' => $version->id,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('mailer_template_langs', [
            'mailer_template_id' => $template->id,
            'subject' => 'Asunto de version antigua',
        ]);
    }

    public function test_restore_saves_backup_version_before_restoring(): void
    {
        $user = $this->createUser(['mailer.templates.update']);
        $langId = $this->createLang();
        $template = $this->createTemplate($langId);

        $version = MailerTemplateVersion::create([
            'mailer_template_id' => $template->id,
            'lang_id' => $langId,
            'created_by' => $user->id,
            'subject' => 'Version para restaurar',
            'content' => '<p>Restauracion</p>',
        ]);

        $countBefore = MailerTemplateVersion::where('mailer_template_id', $template->id)->count();

        $this->actingAs($user)
            ->post(route('mailers.templates.versions.restore', [
                'uid' => $template->uid,
                'version' => $version->id,
            ]));

        $countAfter = MailerTemplateVersion::where('mailer_template_id', $template->id)->count();

        // A new backup snapshot should be created before restoring
        $this->assertGreaterThan($countBefore, $countAfter);
    }

    // -------------------------------------------------------------------------
    // GET /mailers/templates/preview/{uid} — preview
    // -------------------------------------------------------------------------

    public function test_can_preview_template(): void
    {
        $user = $this->createUser(['mailer.templates.update']);
        $langId = $this->createLang();
        $template = $this->createTemplate($langId);

        $this->actingAs($user)
            ->get(route('mailers.templates.preview', ['uid' => $template->uid]))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // DELETE /mailers/templates/{uid} — destroy / protected
    // -------------------------------------------------------------------------

    public function test_cannot_delete_system_template(): void
    {
        $user = $this->createUser(['mailer.templates.delete']);
        $langId = $this->createLang();
        $template = $this->createTemplate($langId, ['is_protected' => true]);

        $this->actingAs($user)
            ->delete(route('mailers.templates.destroy', ['uid' => $template->uid]))
            ->assertRedirect();

        // Template should still exist because it is protected
        $this->assertDatabaseHas('mailer_templates', ['id' => $template->id]);
    }

    public function test_can_delete_non_protected_template(): void
    {
        $user = $this->createUser(['mailer.templates.delete']);
        $langId = $this->createLang();
        $template = $this->createTemplate($langId, ['is_protected' => false]);

        $this->actingAs($user)
            ->delete(route('mailers.templates.destroy', ['uid' => $template->uid]))
            ->assertRedirect(route('mailers.templates.index'));

        $this->assertDatabaseMissing('mailer_templates', ['id' => $template->id]);
    }
}
