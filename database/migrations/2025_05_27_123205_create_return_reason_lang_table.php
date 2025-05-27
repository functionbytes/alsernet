<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnReasonLangTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('return_reason_lang', function (Blueprint $table) {
            $table->unsignedBigInteger('reason_id');
            $table->unsignedBigInteger('lang_id');
            $table->string('name', 64);
            $table->unsignedBigInteger('shop_id');
            $table->timestamps();
            $table->primary(['reason_id', 'lang_id', 'shop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_reason_lang');
    }
};
