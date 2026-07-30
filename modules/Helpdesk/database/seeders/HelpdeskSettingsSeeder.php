<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\Setting;

class HelpdeskSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->settings() as $group => $keys) {
            foreach ($keys as $key => $value) {
                Setting::set("{$group}.{$key}", $value, $group);
            }
        }

        $total = array_sum(array_map('count', $this->settings()));
        $this->command->info("✅ Configuración de helpdesk inicializada ({$total} claves en ".count($this->settings()).' grupos)');
    }

    private function settings(): array
    {
        return [

            // ─── Tickets ──────────────────────────────────────────────────────────
            'tickets' => [
                'customer_ticketid' => 'SPT',
                'ticket_character' => 100,
                'restrict_to_create_ticket' => false,
                'maximum_allow_tickets' => 5,
                'maximum_allow_hours' => 24,
                'restrict_to_reply_ticket' => false,
                'maximum_allow_replies' => 10,
                'reply_allow_in_hours' => 1,
                'auto_responsetime_ticket' => false,
                'auto_responsetime_ticket_time' => 48,
                'auto_close_ticket' => true,
                'auto_close_ticket_time' => 30,
                'user_reopen_issue' => true,
                'user_reopen_time' => 7,
                'auto_overdue_ticket' => false,
                'auto_overdue_ticket_time' => 5,
                'restrict_reply_edit' => false,
                'reply_edit_with_in_time' => 15,
                'auto_overdue_customer' => false,
                'trashed_ticket_autodelete' => true,
                'trashed_ticket_delete_time' => 30,
                'auto_notification_delete_enable' => true,
                'auto_notification_delete_days' => 15,
                'customer_panel_employee_protect' => false,
                'employee_protect_name' => 'Equipo de Soporte',
                'guest_ticket' => true,
                'note_create_mails' => false,
                'restict_to_delete_ticket' => false,
                'user_file_upload_enable' => true,
                'guest_file_upload_enable' => true,
                'guest_ticket_otp' => false,
                'customer_ticket' => false,
                'ticket_rating' => false,
                'cc_email' => false,
            ],

            // ─── Notificaciones ───────────────────────────────────────────────────
            'notifications' => [
                'notify_new_conversation' => true,
                'notify_conversation_assigned' => true,
                'notify_conversation_resolved' => true,
                'notify_new_message' => true,
                'notify_overdue_sla' => true,
                'email_notifications_enabled' => true,
                'browser_notifications_enabled' => false,
                'notification_sound_enabled' => true,
                'daily_digest_enabled' => false,
                'daily_digest_time' => '08:00',
            ],

            // ─── Email ────────────────────────────────────────────────────────────
            'email' => [
                'email_from_name' => 'Soporte Alsernet',
                'email_from_address' => '',
                'email_reply_to' => '',
                'email_signature' => '',
                'outbound_enabled' => false,
                'smtp_host' => '',
                'smtp_port' => 587,
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'tls',
                'inbound_enabled' => false,
                'imap_host' => '',
                'imap_port' => 993,
                'imap_username' => '',
                'imap_password' => '',
                'imap_encryption' => 'ssl',
                'imap_folder' => 'INBOX',
                'auto_create_tickets' => true,
            ],

            // ─── Subida de archivos ───────────────────────────────────────────────
            'uploading' => [
                'max_file_size_mb' => 25,
                'allowed_extensions' => 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip',
                'enable_image_compression' => true,
                'image_max_width' => 1920,
                'image_max_height' => 1080,
                'image_quality' => 85,
                'enable_virus_scan' => false,
                'enable_quarantine' => true,
            ],

            // ─── IA ───────────────────────────────────────────────────────────────
            'ai' => [
                'llm_provider' => 'openai',
                'openai_api_key' => '',
                'openai_model' => 'gpt-4o',
                'anthropic_api_key' => '',
                'anthropic_model' => '.claude-sonnet-4-6',
                'gemini_api_key' => '',
                'gemini_model' => 'gemini-2.0-flash',
                'embeddings_provider' => 'openai',
                'enable_embeddings' => true,
                'enable_rag' => false,
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'top_p' => 1.0,
            ],

        ];
    }
}
