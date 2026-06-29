<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent SEO alerts timeline — score drops, new 404s, redirect chains,
 * webhook failures, etc. Admins can acknowledge/dismiss to mark them resolved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index();           // score_drop, new_404s, redirect_chain, ...
            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
            $table->string('title', 200);
            $table->text('message')->nullable();
            $table->json('context')->nullable();           // structured data (meta_id, url, counts…)
            $table->string('url', 500)->nullable();        // affected URL (if any)
            $table->timestamp('acknowledged_at')->nullable()->index();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamps();

            $table->index(['severity', 'acknowledged_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_alerts');
    }
};
