<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attention_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('P, Q, R, S, F');
            $table->string('name', 100)->comment('Petición, Queja, Reclamo, etc');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attention_types');
    }
};
