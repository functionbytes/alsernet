<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_label_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_label_template_id')->nullable()->constrained('price_label_templates')->nullOnDelete();
            $table->string('template_name');
            $table->string('type');
            $table->unsignedInteger('rows_count')->default(0);
            $table->string('file_path');
            $table->string('file_name');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['price_label_template_id']);
            $table->index(['generated_by']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_label_generations');
    }
};
