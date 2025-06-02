<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('store_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('store_code')->unique();
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->string('province')->nullable();
            $table->string('postal_code');
            $table->string('country')->default('ES');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('opening_hours');
            $table->json('special_hours')->nullable();
            $table->boolean('accepts_returns')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('capacity')->default(100);
            $table->timestamps();

            $table->index(['city', 'is_active']);
            $table->index(['postal_code', 'is_active']);
            $table->index('store_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_locations');
    }
}


