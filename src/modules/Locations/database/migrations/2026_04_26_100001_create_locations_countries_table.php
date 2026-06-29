<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('locations_countries')) {
            return;
        }

        Schema::create('locations_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 10)->unique();
            $table->string('phone_code', 10)->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->string('currency_symbol', 10)->nullable();
            $table->string('flag_emoji', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations_countries');
    }
};
