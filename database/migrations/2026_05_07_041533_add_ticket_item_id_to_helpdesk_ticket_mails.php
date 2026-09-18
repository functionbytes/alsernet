<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_item_id')->nullable()->after('ticket_comment_id');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_ticket_mails', function (Blueprint $table) {
            $table->dropColumn('ticket_item_id');
        });
    }
};
