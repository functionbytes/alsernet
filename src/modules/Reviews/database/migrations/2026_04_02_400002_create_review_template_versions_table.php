<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_reply_template_id')->constrained('review_reply_templates')->cascadeOnDelete();
            $table->text('content');
            $table->string('language', 10)->default('es');
            $table->unsignedInteger('version_number');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_template_versions');
    }
};
