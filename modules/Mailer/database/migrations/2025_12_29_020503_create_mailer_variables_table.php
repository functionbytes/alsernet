<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailer_variables', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('example_value')->nullable();
            $table->string('category')->nullable();
            $table->string('module')->default('core');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index('module');
            $table->index('category');
            $table->unique(['key', 'module'], 'idx_90505');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailer_variables');
    }
};
