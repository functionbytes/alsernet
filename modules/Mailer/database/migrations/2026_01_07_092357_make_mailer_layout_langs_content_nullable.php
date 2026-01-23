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
        Schema::table('mailer_layout_langs', function (Blueprint $table) {
            // Make content field nullable to allow empty strings
            $table->longText('content')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mailer_layout_langs', function (Blueprint $table) {
            // Revert to NOT NULL
            $table->longText('content')->change();
        });
    }
};
