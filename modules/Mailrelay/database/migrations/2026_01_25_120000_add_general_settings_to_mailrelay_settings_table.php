<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mailrelay_settings', function (Blueprint $table) {
            // Sender settings
            $table->string('sender_name')->nullable()->after('id');
            $table->string('sender_email')->nullable()->after('sender_name');
            $table->string('reply_to_email')->nullable()->after('sender_email');

            // Sync settings
            $table->boolean('auto_sync_enabled')->default(false)->after('reply_to_email');
            $table->integer('sync_frequency')->default(60)->comment('Sync frequency in minutes')->after('auto_sync_enabled');
            $table->boolean('sync_deleted')->default(false)->after('sync_frequency');

            // Limits
            $table->integer('emails_per_campaign')->default(1000)->after('sync_deleted');
            $table->integer('retry_attempts')->default(3)->after('emails_per_campaign');
            $table->integer('timeout')->default(30)->comment('API timeout in seconds')->after('retry_attempts');

            // Privacy
            $table->boolean('double_optin')->default(false)->after('timeout');
            $table->boolean('allow_unsubscribe')->default(true)->after('double_optin');
            $table->text('unsubscribe_footer')->nullable()->after('allow_unsubscribe');

            // Advanced
            $table->boolean('detailed_logging')->default(false)->after('unsubscribe_footer');
            $table->integer('log_retention_days')->default(30)->after('detailed_logging');
            $table->boolean('sandbox_mode')->default(false)->after('log_retention_days');
        });

        // Update existing record with default values if it exists
        $existingSettings = DB::table('mailrelay_settings')->first();
        if ($existingSettings) {
            DB::table('mailrelay_settings')
                ->where('id', $existingSettings->id)
                ->update([
                    'sender_name' => config('mail.from.name', 'Example'),
                    'sender_email' => config('mail.from.address', 'hello@example.com'),
                    'auto_sync_enabled' => false,
                    'sync_frequency' => 60,
                    'sync_deleted' => false,
                    'emails_per_campaign' => 1000,
                    'retry_attempts' => 3,
                    'timeout' => 30,
                    'double_optin' => false,
                    'allow_unsubscribe' => true,
                    'detailed_logging' => false,
                    'log_retention_days' => 30,
                    'sandbox_mode' => false,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailrelay_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sender_name',
                'sender_email',
                'reply_to_email',
                'auto_sync_enabled',
                'sync_frequency',
                'sync_deleted',
                'emails_per_campaign',
                'retry_attempts',
                'timeout',
                'double_optin',
                'allow_unsubscribe',
                'unsubscribe_footer',
                'detailed_logging',
                'log_retention_days',
                'sandbox_mode',
            ]);
        });
    }
};
