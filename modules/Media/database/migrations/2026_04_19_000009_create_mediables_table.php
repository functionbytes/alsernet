<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediables', function (Blueprint $table) {
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->unsignedBigInteger('mediable_id');
            $table->string('mediable_type');
            $table->string('collection', 64)->default('default');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->primary(['media_file_id', 'mediable_id', 'mediable_type']);
            $table->index(['mediable_id', 'mediable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
    }
};
