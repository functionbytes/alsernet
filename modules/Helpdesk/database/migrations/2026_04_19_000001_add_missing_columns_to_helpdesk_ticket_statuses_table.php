<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_ticket_statuses', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('name');
            $table->string('icon')->nullable()->after('color');
            $table->boolean('is_closed')->default(false)->after('is_open');
            $table->integer('position')->default(0)->after('order');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_ticket_statuses', function (Blueprint $table) {
            $table->dropColumn(['key', 'icon', 'is_closed', 'position']);
        });
    }
};
