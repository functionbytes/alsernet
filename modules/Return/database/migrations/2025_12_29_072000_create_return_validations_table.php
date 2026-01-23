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
            $table->foreignId('order_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('return_rule_id')->nullable();
            $table->string('validation_status')->nullable();
            $table->json('validation_results')->nullable();
            $table->json('failure_reasons')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('validated_by')->nullable();
            $table->dateTime('validated_at')->nullable();
            $table->timestamps();
            $table->index('order_id');
            $table->index('product_id');
            $table->index('validation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_validations');
    }
};
