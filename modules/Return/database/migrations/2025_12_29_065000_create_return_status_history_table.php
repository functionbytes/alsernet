<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('return_requests')->cascadeOnDelete();
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_status_history');
    }
};
