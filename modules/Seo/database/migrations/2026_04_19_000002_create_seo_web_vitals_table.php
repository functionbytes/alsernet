<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_web_vitals', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('url_path', 500)->nullable()->index();
            $table->string('metric', 16)->comment('LCP, INP, CLS, FCP, TTFB');
            $table->decimal('value', 12, 4)->comment('ms (LCP/INP/FCP/TTFB) o score (CLS)');
            $table->string('rating', 16)->nullable()->comment('good | needs-improvement | poor');
            $table->string('device', 16)->nullable();
            $table->string('connection', 16)->nullable();
            $table->string('navigation_type', 32)->nullable();
            $table->timestamp('captured_at')->index();
            $table->timestamp('created_at')->nullable();

            $table->index(['url_path', 'metric', 'captured_at'], 'idx_wv_path_metric_captured');
            $table->index(['metric', 'captured_at'], 'idx_wv_metric_captured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_web_vitals');
    }
};
