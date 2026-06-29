<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('social_mentions', function (Blueprint $table) {
            $table->foreignId('listening_keyword_id')
                ->nullable()
                ->after('social_account_id')
                ->constrained('social_listening_keywords')
                ->nullOnDelete();

            $table->index('listening_keyword_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_mentions', function (Blueprint $table) {
            $table->dropForeign(['listening_keyword_id']);
            $table->dropColumn('listening_keyword_id');
        });
    }
};
