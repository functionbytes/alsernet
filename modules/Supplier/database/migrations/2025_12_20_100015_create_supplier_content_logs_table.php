<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_content_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('supplier_ai_contents')->onDelete('cascade')->comment('FK to supplier_ai_contents');
            $table->string('action', 50)->comment('Action performed');
            $table->string('previous_status', 50)->nullable()->comment('Previous status');
            $table->string('new_status', 50)->nullable()->comment('New status');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('FK to users (nullable if automatic)');
            $table->json('details')->nullable()->comment('Additional details');
            $table->string('ip_address', 45)->nullable()->comment('User IP address');
            $table->timestamp('created_at')->useCurrent()->comment('When action occurred');

            $table->index(['content_id', 'created_at'], 'logs_content_created_idx');
            $table->index(['action', 'created_at'], 'logs_action_created_idx');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_content_logs');
    }
};
