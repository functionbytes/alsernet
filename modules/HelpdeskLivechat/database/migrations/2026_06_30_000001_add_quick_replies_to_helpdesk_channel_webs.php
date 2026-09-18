<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'helpdesk';
    }

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_channel_webs', function (Blueprint $table) {
            $table->json('quick_replies')->nullable()->after('queue_message');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_channel_webs', function (Blueprint $table) {
            $table->dropColumn('quick_replies');
        });
    }
};
