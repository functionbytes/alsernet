<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitemap_generations', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('success');
            $table->unsignedInteger('url_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->text('error_message')->nullable();
            $table->string('source', 20)->default('command');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_generations');
    }
};
