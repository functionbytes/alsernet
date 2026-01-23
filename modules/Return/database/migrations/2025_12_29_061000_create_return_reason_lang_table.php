<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_reason_lang', function (Blueprint $table) {
            $table->foreignId('id_return_reason')->constrained('return_reasons', 'id_return_reason');
            $table->integer('id_lang');
            $table->integer('id_shop');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_reason_lang');
    }
};
