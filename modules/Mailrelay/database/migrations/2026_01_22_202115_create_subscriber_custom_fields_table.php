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
        Schema::create('subscriber_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('custom_field_id');
            $table->text('value')->nullable(); // Value of the custom field
            $table->timestamps();

            // Foreign keys
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('subscribers')
                ->onDelete('cascade');

            $table->foreign('custom_field_id')
                ->references('id')
                ->on('custom_fields')
                ->onDelete('cascade');

            // Unique constraint: one value per subscriber per field
            $table->unique(['subscriber_id', 'custom_field_id'], 'subscriber_custom_field_unique');

            // Indexes for faster queries
            $table->index('subscriber_id');
            $table->index('custom_field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriber_custom_fields');
    }
};
