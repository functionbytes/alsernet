<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_customers', function (Blueprint $table) {
            $table->tinyInteger('preferred_send_hour')->unsigned()->nullable()->after('birthday')
                ->comment('0-23 UTC hour with highest open rate for this customer');
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_customers', function (Blueprint $table) {
            $table->dropColumn('preferred_send_hour');
        });
    }
};
