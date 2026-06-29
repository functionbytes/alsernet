<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortcodes', function (Blueprint $table): void {
            $table->string('category', 50)->nullable()->default('otros')->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('shortcodes', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }
};
