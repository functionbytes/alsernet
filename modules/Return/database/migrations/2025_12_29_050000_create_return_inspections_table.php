<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_item_id')->constrained('return_request_products')->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable();
            $table->dateTime('inspection_date')->nullable();
            $table->string('condition_grade')->nullable();
            $table->json('checklist_results')->nullable();
            $table->string('final_decision')->nullable();
            $table->json('inspection_photos')->nullable();
            $table->longText('notes')->nullable();
            $table->boolean('requires_review')->default(false);
            $table->foreignId('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->index('return_item_id');
            $table->index('condition_grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_inspections');
    }
};
