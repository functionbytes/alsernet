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
        Schema::create('warehouse_location_sections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('code');
            $table->string('barcode')->nullable();
            $table->integer('level')->default(1);
            $table->boolean('available')->default(true);
            $table->enum('face', ['front', 'back'])->nullable();
            $table->foreignId('location_id')->constrained('warehouse_locations')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('location_id');
            $table->index('code');
            $table->index('barcode');
            $table->index('level');
            $table->index('available');

            // Unique constraint per location
            $table->unique(['location_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_location_sections');
    }
};
