<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('locations_states')) {
            return;
        }

        Schema::create('locations_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('locations_countries')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('country_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations_states');
    }
};
