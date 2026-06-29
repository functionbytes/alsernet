<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_messages', function (Blueprint $table) {
            $table->boolean('is_holdout')->default(false)->after('status');
            $table->index(['campaign_id', 'is_holdout']);
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_messages', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'is_holdout']);
            $table->dropColumn('is_holdout');
        });
    }
};
