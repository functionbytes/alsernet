<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->text('translated_comment')->nullable()->after('comment');
            $table->string('detected_language', 10)->nullable()->after('translated_comment');
            $table->timestamp('translation_cached_at')->nullable()->after('detected_language');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn(['translated_comment', 'detected_language', 'translation_cached_at']);
        });
    }
};
