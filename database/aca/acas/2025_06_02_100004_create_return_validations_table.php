<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('return_rule_id')->nullable()->constrained('product_return_rules')->onDelete('set null');
            $table->string('validation_status'); // pending, passed, failed, manual_review
            $table->json('validation_results'); // Resultados detallados de validación
            $table->text('failure_reasons')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_validations');
    }
};
