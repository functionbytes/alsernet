<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('email_logs', 'message_id')) {
                $table->string('message_id', 255)->nullable()->after('subject')->index();
            }

            if (! Schema::hasColumn('email_logs', 'reply_to')) {
                $table->json('reply_to')->nullable()->after('bcc_addresses');
            }

            if (! Schema::hasColumn('email_logs', 'attachments')) {
                $table->json('attachments')->nullable()->after('body_text');
            }

            if (! Schema::hasColumn('email_logs', 'causer_id')) {
                $table->nullableMorphs('causer');
            }
        });

        $this->addIndexIfMissing('email_logs_module_created_at_index', function (Blueprint $table) {
            $table->index(['module', 'created_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_logs')) {
            return;
        }

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropIndex('email_logs_module_created_at_index');

            if (Schema::hasColumn('email_logs', 'causer_id')) {
                $table->dropMorphs('causer');
            }

            $table->dropColumn(array_values(array_filter([
                Schema::hasColumn('email_logs', 'message_id') ? 'message_id' : null,
                Schema::hasColumn('email_logs', 'reply_to') ? 'reply_to' : null,
                Schema::hasColumn('email_logs', 'attachments') ? 'attachments' : null,
            ])));
        });
    }

    private function addIndexIfMissing(string $name, callable $callback): void
    {
        $exists = collect(Schema::getIndexes('email_logs'))->contains(fn ($index) => $index['name'] === $name);

        if (! $exists) {
            Schema::table('email_logs', $callback);
        }
    }
};
