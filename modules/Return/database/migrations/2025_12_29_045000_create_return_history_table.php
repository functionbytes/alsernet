<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_history', function (Blueprint $table) {
            $table->id('id_return_history');
            $table->foreignId('id_return_request')->constrained('return_requests')->cascadeOnDelete();
            $table->foreignId('id_return_status')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('id_employee')->nullable();
            $table->boolean('set_pickup')->default(false);
            $table->boolean('is_refunded')->default(false);
            $table->boolean('shown_to_customer')->default(false);
            $table->timestamps();
            $table->index('id_return_request');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_history');
    }
};
