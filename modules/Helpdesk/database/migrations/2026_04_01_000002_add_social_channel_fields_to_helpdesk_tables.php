<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (config('database.connections.helpdesk') === null) {
            return;
        }

        if (! Schema::connection('helpdesk')->hasTable('helpdesk_conversations')) {
            return;
        }

        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table) {
            $table->string('channel')->nullable()->default('widget')->after('customer_id');
            $table->string('external_id')->nullable()->after('channel');
            $table->string('external_sender_id')->nullable()->after('external_id');

            $table->unique(['channel', 'external_id'], 'helpdesk_conversations_channel_external_id_unique');
        });

        Schema::connection('helpdesk')->table('helpdesk_conversation_items', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('conversation_id');
        });

        Schema::connection('helpdesk')->table('helpdesk_customers', function (Blueprint $table) {
            $table->string('whatsapp_phone')->nullable()->after('phone');
            $table->string('facebook_psid')->nullable()->after('whatsapp_phone');
            $table->string('instagram_id')->nullable()->after('facebook_psid');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropUnique('helpdesk_conversations_channel_external_id_unique');
            $table->dropColumn(['channel', 'external_id', 'external_sender_id']);
        });

        Schema::connection('helpdesk')->table('helpdesk_conversation_items', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });

        Schema::connection('helpdesk')->table('helpdesk_customers', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_phone', 'facebook_psid', 'instagram_id']);
        });
    }
};
