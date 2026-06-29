<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('langs', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('title');
            $table->string('iso_code')->unique();
            $table->string('lenguage_code')->unique();
            $table->string('locate')->nullable();
            $table->string('date_format_full')->nullable();
            $table->string('date_format_lite')->nullable();
            $table->boolean('available')->default(1);
            $table->timestamps();

            $table->index('available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('langs');
    }
};
