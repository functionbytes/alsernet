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
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widget_id')->index();
            $table->string('sidebar_id')->index();
            $table->string('theme')->default('default')->index();
            $table->integer('position')->default(0);
            $table->json('data')->nullable();
            $table->boolean('status')->default(true)->index();
            $table->timestamps();

            $table->index(['sidebar_id', 'theme', 'status']);
            $table->index(['sidebar_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
