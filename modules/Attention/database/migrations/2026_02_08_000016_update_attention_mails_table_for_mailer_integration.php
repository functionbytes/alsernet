<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renames must be in separate Schema::table calls for MariaDB
        if (Schema::hasColumn('attention_mails', 'recipient') && ! Schema::hasColumn('attention_mails', 'recipient_email')) {
            Schema::table('attention_mails', function (Blueprint $table) {
                $table->renameColumn('recipient', 'recipient_email');
            });
        }

        if (Schema::hasColumn('attention_mails', 'body') && ! Schema::hasColumn('attention_mails', 'body_html')) {
            Schema::table('attention_mails', function (Blueprint $table) {
                $table->renameColumn('body', 'body_html');
            });
        }

        // Add new columns
        Schema::table('attention_mails', function (Blueprint $table) {
            if (! Schema::hasColumn('attention_mails', 'uid')) {
                $table->string('uid', 36)->unique()->after('id')->nullable();
            }
            if (! Schema::hasColumn('attention_mails', 'body_text')) {
                $table->longText('body_text')->nullable()->after('body_html')->comment('Cuerpo del email en texto plano');
            }
            if (! Schema::hasColumn('attention_mails', 'template_id')) {
                $table->foreignId('template_id')->nullable()->after('body_text')->constrained('mailer_templates')->onDelete('set null')->comment('Plantilla de email utilizada');
            }
            if (! Schema::hasColumn('attention_mails', 'sent_by')) {
                $table->foreignId('sent_by')->nullable()->after('template_id')->constrained('users')->onDelete('set null')->comment('Usuario que envió el email');
            }
            if (! Schema::hasColumn('attention_mails', 'metadata')) {
                $table->json('metadata')->nullable()->after('sent_by')->comment('Metadatos adicionales del email');
            }
            if (! Schema::hasColumn('attention_mails', 'status')) {
                $table->string('status', 50)->default('queued')->after('metadata')->comment('Estado del email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attention_mails', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                'uid', 'body_text', 'template_id', 'sent_by', 'metadata', 'status',
            ], fn (string $col) => Schema::hasColumn('attention_mails', $col)));
        });

        if (Schema::hasColumn('attention_mails', 'recipient_email')) {
            Schema::table('attention_mails', function (Blueprint $table) {
                $table->renameColumn('recipient_email', 'recipient');
            });
        }

        if (Schema::hasColumn('attention_mails', 'body_html')) {
            Schema::table('attention_mails', function (Blueprint $table) {
                $table->renameColumn('body_html', 'body');
            });
        }
    }
};
