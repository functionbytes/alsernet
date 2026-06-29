<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_cache_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->boolean('cache_enabled')->default(true);
            $table->boolean('warm_on_save')->default(false);
            $table->json('excluded_roles')->nullable();
            $table->timestamps();

            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->unique('page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_cache_configs');
    }
};
