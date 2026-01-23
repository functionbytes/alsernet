<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('return_requests')->cascadeOnDelete();
            $table->string('cost_type')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->foreignId('applied_by')->nullable();
            $table->timestamps();
            $table->index('return_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_costs');
    }
};
