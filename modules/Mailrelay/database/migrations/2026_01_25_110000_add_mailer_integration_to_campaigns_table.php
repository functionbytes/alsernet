<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la tabla existe, si no, crearla
        if (! Schema::hasTable('mails_campaigns')) {
            Schema::create('mails_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('mailrelay_campaign_id')->nullable();
                $table->string('name');
                $table->string('subject');
                $table->longText('html_content')->nullable();
                $table->longText('text_content')->nullable();
                $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('draft');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->integer('recipient_count')->default(0);
                $table->json('metadata')->nullable();
                $table->foreignId('list_id')->nullable()->constrained('lists')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Agregar nuevos campos para integración con Mailer
        Schema::table('mails_campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('mails_campaigns', 'mailer_template_id')) {
                $table->foreignId('mailer_template_id')->nullable()
                    ->after('html_content')
                    ->constrained('mailer_templates')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('mails_campaigns', 'lang_id')) {
                $table->foreignId('lang_id')->nullable()
                    ->after('mailer_template_id')
                    ->constrained('langs')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('mails_campaigns', 'mail_provider_id')) {
                $table->foreignId('mail_provider_id')->nullable()
                    ->after('lang_id')
                    ->constrained('mail_providers')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('mails_campaigns', 'template_variables')) {
                $table->json('template_variables')->nullable()
                    ->after('mail_provider_id');
            }

            if (! Schema::hasColumn('mails_campaigns', 'track_opens')) {
                $table->boolean('track_opens')->default(true)
                    ->after('template_variables');
            }

            if (! Schema::hasColumn('mails_campaigns', 'track_clicks')) {
                $table->boolean('track_clicks')->default(true)
                    ->after('track_opens');
            }

            if (! Schema::hasColumn('mails_campaigns', 'provider_campaign_id')) {
                $table->string('provider_campaign_id')->nullable()
                    ->after('track_clicks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mails_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('mails_campaigns', 'mailer_template_id')) {
                $table->dropForeign(['mailer_template_id']);
                $table->dropColumn('mailer_template_id');
            }

            if (Schema::hasColumn('mails_campaigns', 'lang_id')) {
                $table->dropForeign(['lang_id']);
                $table->dropColumn('lang_id');
            }

            if (Schema::hasColumn('mails_campaigns', 'mail_provider_id')) {
                $table->dropForeign(['mail_provider_id']);
                $table->dropColumn('mail_provider_id');
            }

            if (Schema::hasColumn('mails_campaigns', 'template_variables')) {
                $table->dropColumn('template_variables');
            }

            if (Schema::hasColumn('mails_campaigns', 'track_opens')) {
                $table->dropColumn('track_opens');
            }

            if (Schema::hasColumn('mails_campaigns', 'track_clicks')) {
                $table->dropColumn('track_clicks');
            }

            if (Schema::hasColumn('mails_campaigns', 'provider_campaign_id')) {
                $table->dropColumn('provider_campaign_id');
            }
        });
    }
};
