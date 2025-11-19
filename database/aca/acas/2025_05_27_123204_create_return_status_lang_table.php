<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnStatusLangTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('return_status_lang', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('lang_id');
            $table->string('name', 64);
            $table->text('mail_description')->nullable();
            $table->unsignedBigInteger('shop_id');
            $table->timestamps();

            $table->primary(['status_id', 'lang_id', 'shop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_status_lang');
    }
};
