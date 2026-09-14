<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_customers', function (Blueprint $table) {
            $table->index('whatsapp_phone', 'helpdesk_customers_whatsapp_phone_index');
            $table->index('facebook_psid', 'helpdesk_customers_facebook_psid_index');
            $table->index('instagram_id', 'helpdesk_customers_instagram_id_index');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_customers', function (Blueprint $table) {
            $table->dropIndex('helpdesk_customers_whatsapp_phone_index');
            $table->dropIndex('helpdesk_customers_facebook_psid_index');
            $table->dropIndex('helpdesk_customers_instagram_id_index');
        });
    }
};
