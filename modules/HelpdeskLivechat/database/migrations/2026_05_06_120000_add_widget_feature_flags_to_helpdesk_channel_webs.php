<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_channel_webs', function (Blueprint $table) {
            // Feature flags — home screen
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'show_avatars')) {
                $table->boolean('show_avatars')->default(true);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'show_help_center')) {
                $table->boolean('show_help_center')->default(true);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'hide_suggested_articles')) {
                $table->boolean('hide_suggested_articles')->default(false);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'show_tickets_section')) {
                $table->boolean('show_tickets_section')->default(true);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_send_message')) {
                $table->boolean('enable_send_message')->default(true);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_create_ticket')) {
                $table->boolean('enable_create_ticket')->default(true);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_search_help')) {
                $table->boolean('enable_search_help')->default(true);
            }

            // Chat screen text
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'welcome_message')) {
                $table->string('welcome_message', 200)->default('Hola! ¿Cómo podemos ayudarte?');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'input_placeholder')) {
                $table->string('input_placeholder', 100)->default('Escribe tu mensaje...');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'queue_message')) {
                $table->string('queue_message', 500)->nullable();
            }

            // Launcher
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'side_spacing')) {
                $table->unsignedSmallInteger('side_spacing')->default(16);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'bottom_spacing')) {
                $table->unsignedSmallInteger('bottom_spacing')->default(16);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'hide_launcher')) {
                $table->boolean('hide_launcher')->default(false);
            }

            // Style / branding
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'secondary_color')) {
                $table->string('secondary_color', 20)->default('#ffffff');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'header_title')) {
                $table->string('header_title', 100)->default('Chat de Soporte');
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'logo_url')) {
                $table->string('logo_url', 500)->nullable();
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'team_avatars')) {
                $table->json('team_avatars')->nullable();
            }

            // Additional feature flags — chat screen
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'show_timestamps')) {
                $table->boolean('show_timestamps')->default(true);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'typing_indicator')) {
                $table->boolean('typing_indicator')->default(true);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'sound_notifications')) {
                $table->boolean('sound_notifications')->default(true);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_email_transcripts')) {
                $table->boolean('enable_email_transcripts')->default(false);
            }

            // Automation / timeouts
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_auto_transfer')) {
                $table->boolean('enable_auto_transfer')->default(false);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'auto_transfer_minutes')) {
                $table->unsignedTinyInteger('auto_transfer_minutes')->default(5);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_auto_inactive')) {
                $table->boolean('enable_auto_inactive')->default(false);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'auto_inactive_minutes')) {
                $table->unsignedTinyInteger('auto_inactive_minutes')->default(10);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enable_auto_close')) {
                $table->boolean('enable_auto_close')->default(false);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'auto_close_minutes')) {
                $table->unsignedTinyInteger('auto_close_minutes')->default(15);
            }

            // Security
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'trusted_domains')) {
                $table->text('trusted_domains')->nullable();
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'enforce_identity_verification')) {
                $table->boolean('enforce_identity_verification')->default(false);
            }

            // Forms
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'pre_chat_info')) {
                $table->string('pre_chat_info', 300)->nullable();
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'post_chat_form_enabled')) {
                $table->boolean('post_chat_form_enabled')->default(false);
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'post_chat_info')) {
                $table->string('post_chat_info', 300)->nullable();
            }

            // Chat page
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'chat_page_title')) {
                $table->string('chat_page_title', 100)->nullable();
            }
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'chat_page_subtitle')) {
                $table->string('chat_page_subtitle', 200)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_channel_webs', function (Blueprint $table) {
            $table->dropColumn([
                'show_avatars', 'show_help_center', 'hide_suggested_articles',
                'show_tickets_section', 'enable_send_message', 'enable_create_ticket',
                'enable_search_help', 'welcome_message', 'input_placeholder',
                'queue_message', 'side_spacing', 'bottom_spacing', 'hide_launcher',
                'secondary_color', 'header_title', 'logo_url', 'team_avatars',
                'show_timestamps', 'typing_indicator', 'sound_notifications',
                'enable_email_transcripts', 'enable_auto_transfer', 'auto_transfer_minutes',
                'enable_auto_inactive', 'auto_inactive_minutes', 'enable_auto_close',
                'auto_close_minutes', 'trusted_domains', 'enforce_identity_verification',
                'pre_chat_info', 'post_chat_form_enabled', 'post_chat_info',
                'chat_page_title', 'chat_page_subtitle',
            ]);
        });
    }
};
