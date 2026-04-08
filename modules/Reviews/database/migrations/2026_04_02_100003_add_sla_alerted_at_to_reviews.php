<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('sla_alerted_at')
                ->nullable()
                ->after('synced_at')
                ->comment('When the SLA breach alert was last sent for this review');

            $table->index('sla_alerted_at');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['sla_alerted_at']);
            $table->dropColumn('sla_alerted_at');
        });
    }
};
