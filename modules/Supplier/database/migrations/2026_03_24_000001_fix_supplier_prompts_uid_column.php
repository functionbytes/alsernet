<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            // Change uid column from char(26) to char(36) to accommodate UUIDs from HasUid trait
            $table->char('uid', 36)->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_prompts', function (Blueprint $table) {
            $table->char('uid', 36)->change();
        });
    }
};
