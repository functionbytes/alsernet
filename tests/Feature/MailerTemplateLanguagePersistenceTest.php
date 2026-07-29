<?php

namespace Tests\Feature;

use App\Models\Lang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;
use Tests\TestCase;

/**
 * Test to validate that language selection persists when editing email templates
 * Issue: Clicking on a different language (e.g., English) and saving should redirect back to that language,
 * not default to Spanish (lang_id=1)
 */
class MailerTemplateLanguagePersistenceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that language selection persists after saving template changes
     *
     * Scenario:
     * 1. Access edit view with lang_id=2 (English)
     * 2. Make changes and save
     * 3. Should redirect back to edit view with lang_id=2, NOT lang_id=1 (Spanish)
     */
    public function test_language_id_persists_after_saving_template(): void
    {
        // Setup: Get available languages
        $englishLang = Lang::where('code', 'en')->first() ?? Lang::create([
            'title' => 'English',
            'code' => 'en',
            'is_enabled' => true,
        ]);

        $spanishLang = Lang::where('code', 'es')->first() ?? Lang::create([
            'title' => 'Español',
            'code' => 'es',
            'is_enabled' => true,
        ]);

        // Create a test email template
        $template = MailerTemplate::create([
            'key' => 'test.language.persistence',
            'name' => 'Test Language Persistence Template',
            'module' => 'core',
            'is_enabled' => true,
            'is_protected' => false,
        ]);

        // Create translations for both languages
        MailerTemplateLang::create([
            'email_template_id' => $template->id,
            'lang_id' => $spanishLang->id,
            'subject' => 'Asunto en Español',
            'content' => '<p>Contenido en Español</p>',
        ]);

        MailerTemplateLang::create([
            'email_template_id' => $template->id,
            'lang_id' => $englishLang->id,
            'subject' => 'Subject in English',
            'content' => '<p>Content in English</p>',
        ]);

        // Step 1: Access edit view with English (lang_id=2)
        $response = $this->get(
            route('manager.backups.mailers.templates.edit', [
                'uid' => $template->uid,
                'lang_id' => $englishLang->id,
            ])
        );

        // Should load without errors
        $response->assertStatus(200);
        // Should display English translation
        $response->assertSeeText('Subject in English');

        // Step 2: Submit form with changes while on English language
        $updateResponse = $this->patch(
            route('manager.backups.mailers.templates.update', $template->uid),
            [
                'subject' => 'Updated Subject in English',
                'content' => '<p>Updated Content in English</p>',
                'lang_id' => $englishLang->id,
                'is_enabled' => true,
            ]
        );

        // Step 3: Verify redirect maintains language
        // PROBLEM: Currently redirects with ->back() which loses the lang_id parameter
        // EXPECTED: Should redirect to edit view with lang_id=2 (English)
        // ACTUAL: Redirects to previous page (usually with lang_id=1 or no lang_id, defaulting to Spanish)

        $updateResponse->assertSessionHasNoErrors();

        // The critical assertion: After update, should redirect back to English, not Spanish
        $updateResponse->assertRedirect(
            route('manager.backups.mailers.templates.edit', [
                'uid' => $template->uid,
                'lang_id' => $englishLang->id,
            ])
        );

        // Verify the translation was actually updated in English
        $updated = $template->translate($englishLang->id);
        $this->assertEquals('Updated Subject in English', $updated->subject);
        $this->assertStringContainsString('Updated Content in English', $updated->content);

        // Spanish translation should remain unchanged
        $spanishTranslation = $template->translate($spanishLang->id);
        $this->assertEquals('Asunto en Español', $spanishTranslation->subject);
    }

    /**
     * Test that switching between languages works correctly
     *
     * Scenario:
     * 1. Start editing in Spanish
     * 2. Click link to switch to English
     * 3. Page loads with English content
     * 4. Make changes and save
     * 5. Should stay on English, not revert to Spanish
     */
    public function test_switching_languages_maintains_selection(): void
    {
        $englishLang = Lang::where('code', 'en')->first() ?? Lang::create([
            'title' => 'English',
            'code' => 'en',
            'is_enabled' => true,
        ]);

        $spanishLang = Lang::where('code', 'es')->first() ?? Lang::create([
            'title' => 'Español',
            'code' => 'es',
            'is_enabled' => true,
        ]);

        $template = MailerTemplate::create([
            'key' => 'test.language.switch',
            'name' => 'Test Language Switch',
            'module' => 'core',
        ]);

        MailerTemplateLang::create([
            'email_template_id' => $template->id,
            'lang_id' => $spanishLang->id,
            'subject' => 'Asunto ES',
            'content' => '<p>ES</p>',
        ]);

        MailerTemplateLang::create([
            'email_template_id' => $template->id,
            'lang_id' => $englishLang->id,
            'subject' => 'Subject EN',
            'content' => '<p>EN</p>',
        ]);

        // Start on Spanish
        $response = $this->get(
            route('manager.backups.mailers.templates.edit', [
                'uid' => $template->uid,
                'lang_id' => $spanishLang->id,
            ])
        );
        $response->assertSeeText('Asunto ES');

        // Click to switch to English (this is an <a> link, not a form)
        $englishResponse = $this->get(
            route('manager.backups.mailers.templates.edit', [
                'uid' => $template->uid,
                'lang_id' => $englishLang->id,
            ])
        );

        // Verify English content is shown
        $englishResponse->assertSeeText('Subject EN');

        // Now save changes while on English
        $updateResponse = $this->patch(
            route('manager.backups.mailers.templates.update', $template->uid),
            [
                'subject' => 'Updated EN Subject',
                'content' => '<p>Updated EN Content</p>',
                'lang_id' => $englishLang->id,
                'is_enabled' => true,
            ]
        );

        // Should redirect back to English, not Spanish
        $updateResponse->assertRedirect(
            route('manager.backups.mailers.templates.edit', [
                'uid' => $template->uid,
                'lang_id' => $englishLang->id,
            ])
        );
    }
}
