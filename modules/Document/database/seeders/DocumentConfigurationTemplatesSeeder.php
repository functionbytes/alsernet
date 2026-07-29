<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Mailer\Models\MailerTemplate;

class DocumentConfigurationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // Get template IDs
        $templates = [
            'initial_request' => MailerTemplate::where('key', 'document_initial_request')->where('module', 'documents')->first()?->id,
            'reminder' => MailerTemplate::where('key', 'document_reminder')->where('module', 'documents')->first()?->id,
            'missing_docs' => MailerTemplate::where('key', 'document_missing_documents')->where('module', 'documents')->first()?->id,
            'upload_confirmation' => MailerTemplate::where('key', 'document_completed')->where('module', 'documents')->first()?->id,
            'approval' => MailerTemplate::where('key', 'document_approved')->where('module', 'documents')->first()?->id,
            'rejection' => MailerTemplate::where('key', 'document_rejected')->where('module', 'documents')->first()?->id,
        ];

        // Update settings with template IDs
        if ($templates['initial_request']) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'documents.mail_template_initial_request_id'],
                ['value' => (string)$templates['initial_request'], 'updated_at' => now()]
            );
            $this->command->info("✓ Initial request template linked: {$templates['initial_request']}");
        }

        if ($templates['reminder']) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'documents.mail_template_reminder_id'],
                ['value' => (string)$templates['reminder'], 'updated_at' => now()]
            );
            $this->command->info("✓ Reminder template linked: {$templates['reminder']}");
        }

        if ($templates['missing_docs']) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'documents.mail_template_missing_docs_id'],
                ['value' => (string)$templates['missing_docs'], 'updated_at' => now()]
            );
            $this->command->info("✓ Missing docs template linked: {$templates['missing_docs']}");
        }

        if ($templates['upload_confirmation']) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'documents.mail_template_upload_confirmation_id'],
                ['value' => (string)$templates['upload_confirmation'], 'updated_at' => now()]
            );
            $this->command->info("✓ Upload confirmation template linked: {$templates['upload_confirmation']}");
        }

        if ($templates['approval']) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'documents.mail_template_approval_id'],
                ['value' => (string)$templates['approval'], 'updated_at' => now()]
            );
            $this->command->info("✓ Approval template linked: {$templates['approval']}");
        }

        if ($templates['rejection']) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'documents.mail_template_rejection_id'],
                ['value' => (string)$templates['rejection'], 'updated_at' => now()]
            );
            $this->command->info("✓ Rejection template linked: {$templates['rejection']}");
        }

        $this->command->info('✅ Document configuration templates linked successfully');
    }
}
