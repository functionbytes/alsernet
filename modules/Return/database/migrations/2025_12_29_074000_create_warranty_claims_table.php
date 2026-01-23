<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('warranty_id')->constrained('warranties')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('issue_category')->nullable();
            $table->longText('issue_description')->nullable();
            $table->date('issue_occurred_date')->nullable();
            $table->json('symptoms')->nullable();
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->decimal('estimated_repair_cost', 10, 2)->nullable();
            $table->longText('resolution_description')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->string('assigned_department')->nullable();
            $table->string('manufacturer_claim_id')->nullable();
            $table->string('manufacturer_status')->nullable();
            $table->json('manufacturer_response')->nullable();
            $table->dateTime('submitted_to_manufacturer_at')->nullable();
            $table->dateTime('manufacturer_response_at')->nullable();
            $table->string('resolution_type')->nullable();
            $table->decimal('resolution_cost', 10, 2)->nullable();
            $table->text('customer_satisfaction_notes')->nullable();
            $table->integer('customer_rating')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable();
            $table->json('status_history')->nullable();
            $table->json('communication_log')->nullable();
            $table->json('attachments')->nullable();
            $table->dateTime('response_due_date')->nullable();
            $table->dateTime('resolution_due_date')->nullable();
            $table->boolean('sla_met')->nullable();
            $table->integer('total_resolution_hours')->nullable();
            $table->timestamps();
            $table->index('claim_number');
            $table->index('warranty_id');
            $table->index('status');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_warranty_claims');
    }
};
