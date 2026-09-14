<?php

namespace Modules\Mailer\Database\Seeders\Migrations;

use Illuminate\Database\Seeder;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Mailer\Models\MailerTemplateLang;

class MigrateTemplateTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting email template translations migration...');

        $templates = MailerTemplate::all();
        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($templates as $template) {
            $langId = $template->lang_id ?? 1;

            // Check if translation already exists
            $exists = MailerTemplateLang::where('mailer_template_id', $template->id)
                ->where('lang_id', $langId)
                ->exists();

            if (! $exists && ($template->subject || $template->content)) {
                MailerTemplateLang::create([
                    'mailer_template_id' => $template->id,
                    'lang_id' => $langId,
                    'subject' => $template->subject,
                    'preheader' => null,
                    'content' => $template->content,
                ]);

                $this->command->info("  ✓ Migrated: Template ID {$template->id} → Lang ID {$langId}");
                $migratedCount++;
            } else {
                $skippedCount++;
            }
        }

        $this->command->info('✓ Email template translations migration completed!');
        $this->command->info("  - Migrated: {$migratedCount}");
        $this->command->info("  - Skipped: {$skippedCount}");
    }
}
