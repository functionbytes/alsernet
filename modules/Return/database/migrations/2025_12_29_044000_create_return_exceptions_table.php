<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->cascadeOnDelete();
            $table->foreignId('return_inspection_id')->nullable()->constrained('return_inspections')->cascadeOnDelete();
            $table->string('exception_type')->nullable();
            $table->longText('description')->nullable();
            $table->string('severity')->nullable();
            $table->string('resolution')->nullable();
            $table->decimal('compensation_amount', 10, 2)->nullable();
            $table->foreignId('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->longText('resolution_notes')->nullable();
            $table->boolean('requires_escalation')->default(false);
            $table->foreignId('escalated_to')->nullable();
            $table->dateTime('escalated_at')->nullable();
            $table->timestamps();
            $table->index('return_request_id');
            $table->index('return_inspection_id');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_exceptions');
    }
};
