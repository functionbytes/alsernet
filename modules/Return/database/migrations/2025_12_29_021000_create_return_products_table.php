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
        Schema::create('inventaries', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('reference')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('available')->default(0);
            $table->integer('management')->default(0);
            $table->integer('kardex')->default(0);
            $table->integer('validate')->default(0);
            $table->integer('count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventaries');
    }
};
