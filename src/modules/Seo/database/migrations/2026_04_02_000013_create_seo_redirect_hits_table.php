<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_redirect_hits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seo_redirect_id')->constrained('seo_redirects')->cascadeOnDelete();
            $table->date('hit_date');
            $table->unsignedInteger('hit_count')->default(0);
            $table->unique(['seo_redirect_id', 'hit_date']);
            $table->index('hit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_redirect_hits');
    }
};
