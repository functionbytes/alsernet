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
        Schema::table('shortcodes', function (Blueprint $table) {
            $table->text('css_code')->nullable()->after('render_template');
        });
    }

    public function down(): void
    {
        Schema::table('shortcodes', function (Blueprint $table) {
            $table->dropColumn('css_code');
        });
    }
};
