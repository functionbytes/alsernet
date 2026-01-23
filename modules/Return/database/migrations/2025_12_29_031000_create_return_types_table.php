<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_types', function (Blueprint $table) {
            $table->id('id_return_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_types');
    }
};
