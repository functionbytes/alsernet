<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_reply_templates', function (Blueprint $table) {
            $table->string('language', 10)->default('es')
                ->after('category')
                ->comment('ISO 639-1 language code for this template');

            $table->index('language');
        });
    }

    public function down(): void
    {
        Schema::table('review_reply_templates', function (Blueprint $table) {
            $table->dropIndex(['language']);
            $table->dropColumn('language');
        });
    }
};
