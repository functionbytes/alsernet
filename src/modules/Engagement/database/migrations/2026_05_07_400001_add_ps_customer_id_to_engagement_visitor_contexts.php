<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::table('engagement_visitor_contexts', function (Blueprint $table) {
            $table->unsignedBigInteger('ps_customer_id')->nullable()->after('customer_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('engagement_visitor_contexts', function (Blueprint $table) {
            $table->dropIndex(['ps_customer_id']);
            $table->dropColumn('ps_customer_id');
        });
    }
};
