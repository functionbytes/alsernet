<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_inboxes', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_inboxes', 'channel_id')) {
                $table->unsignedBigInteger('channel_id')->nullable()->index('hd_inboxes_channel_id_index');
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_inboxes', 'account_id')) {
                $table->unsignedBigInteger('account_id')->nullable()->index('hd_inboxes_account_id_index');
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_inboxes', 'timezone')) {
                $table->string('timezone')->nullable()->default('UTC');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_inboxes', function (Blueprint $table) {
            if (Schema::connection('helpdesk')->hasColumn('helpdesk_inboxes', 'channel_id')) {
                $table->dropColumn('channel_id');
            }

            if (Schema::connection('helpdesk')->hasColumn('helpdesk_inboxes', 'account_id')) {
                $table->dropColumn('account_id');
            }

            if (Schema::connection('helpdesk')->hasColumn('helpdesk_inboxes', 'timezone')) {
                $table->dropColumn('timezone');
            }
        });
    }
};
