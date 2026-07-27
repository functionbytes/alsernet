<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_messages', function (Blueprint $table) {
            $table->boolean('is_test')->default(false)->after('is_holdout');
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_messages', function (Blueprint $table) {
            $table->dropColumn('is_test');
        });
    }
};
