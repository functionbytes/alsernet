<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_ip_locations', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('ip_address')->unique();
            $table->string('country_code')->nullable();
            $table->string('country_name')->nullable();
            $table->string('region_code')->nullable();
            $table->string('region_name')->nullable();
            $table->string('city')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('metro_code')->nullable();
            $table->string('areacode')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_ip_locations');
    }
};
