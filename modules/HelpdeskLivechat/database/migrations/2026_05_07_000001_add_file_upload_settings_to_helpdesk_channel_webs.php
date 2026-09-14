<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_channel_webs', function (Blueprint $table) {
            $table->boolean('enable_file_upload')->default(true)->after('enable_send_message');
            $table->boolean('enable_emoji')->default(true)->after('enable_file_upload');
            $table->text('allowed_file_types')->nullable()->after('enable_emoji');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_channel_webs', function (Blueprint $table) {
            $table->dropColumn(['enable_file_upload', 'enable_emoji', 'allowed_file_types']);
        });
    }
};
