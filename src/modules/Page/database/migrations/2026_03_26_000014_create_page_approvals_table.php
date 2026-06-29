<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->index(['page_id', 'status']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_approvals');
    }
};
