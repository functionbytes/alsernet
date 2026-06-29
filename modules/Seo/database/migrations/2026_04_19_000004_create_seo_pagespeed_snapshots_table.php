<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historical PageSpeed Insights snapshots. Populated weekly by seo:pagespeed
 * so admins can track performance trends over time per URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_pagespeed_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('url_path', 500)->index();
            $table->enum('strategy', ['mobile', 'desktop'])->default('mobile');

            $table->tinyInteger('performance')->nullable();
            $table->tinyInteger('accessibility')->nullable();
            $table->tinyInteger('best_practices')->nullable();
            $table->tinyInteger('seo')->nullable();

            // Core Web Vitals (in ms, except CLS which is a dimensionless score)
            $table->decimal('lcp_ms', 10, 2)->nullable();
            $table->decimal('inp_ms', 10, 2)->nullable();
            $table->decimal('cls', 6, 3)->nullable();
            $table->decimal('fcp_ms', 10, 2)->nullable();
            $table->decimal('ttfb_ms', 10, 2)->nullable();

            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['url_path', 'strategy', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pagespeed_snapshots');
    }
};
