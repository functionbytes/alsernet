<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shortcodes', function (Blueprint $table) {
            $table->mediumText('render_template')->nullable()->default('')->change();
        });
    }

    public function down(): void
    {
        Schema::table('shortcodes', function (Blueprint $table) {
            $table->text('render_template')->nullable()->default('')->change();
        });
    }
};
