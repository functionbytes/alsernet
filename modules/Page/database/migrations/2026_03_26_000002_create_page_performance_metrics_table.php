<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->enum('strategy', ['mobile', 'desktop']);
            $table->float('performance_score')->nullable();
            $table->float('accessibility_score')->nullable();
            $table->float('seo_score')->nullable();
            $table->float('best_practices_score')->nullable();
            $table->integer('lcp')->nullable();
            $table->integer('fid')->nullable();
            $table->integer('cls_score')->nullable();
            $table->integer('fcp')->nullable();
            $table->integer('ttfb')->nullable();
            $table->integer('tbt')->nullable();
            $table->integer('speed_index')->nullable();
            $table->json('opportunities')->nullable();
            $table->json('diagnostics')->nullable();
            $table->string('page_url');
            $table->timestamps();

            $table->index(['page_id', 'strategy', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_performance_metrics');
    }
};
