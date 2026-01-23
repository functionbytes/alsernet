<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_status_lang', function (Blueprint $table) {
            $table->foreignId('id_return_status')->constrained('return_status', 'id');
            $table->integer('id_lang');
            $table->integer('id_shop');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_status_lang');
    }
};
