<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('provider', 100);
            $table->string('category', 30);
            $table->string('purpose', 255);
            $table->string('duration', 50);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_inventory');
    }
};
