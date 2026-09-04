<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->renameColumn('button_text', 'submit_button_text');
            $table->renameColumn('notify_admin_email', 'admin_notification_email');

            $table->string('confirmation_subject', 255)->nullable()->after('send_confirmation');
            $table->text('confirmation_message')->nullable()->after('confirmation_subject');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->renameColumn('submit_button_text', 'button_text');
            $table->renameColumn('admin_notification_email', 'notify_admin_email');

            $table->dropColumn(['confirmation_subject', 'confirmation_message']);
        });
    }
};
