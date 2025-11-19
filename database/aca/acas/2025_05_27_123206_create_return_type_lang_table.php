<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnTypeLangTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('return_type_lang', function (Blueprint $table) {
            $table->string('name', 64);
            $table->integer('day');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('lang_id');
            $table->unsignedBigInteger('type_id');
            $table->string('return_color', 32)->nullable();
            $table->tinyInteger('active');
            $table->timestamps();

            $table->primary(['type_id', 'lang_id', 'shop_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_type_lang');
    }
};
