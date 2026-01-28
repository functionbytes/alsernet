<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscribersTable extends Migration
{
    public function up()
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('list_id');
            $table->boolean('is_subscribed')->default(true); // Para indicar si está suscrito
            $table->timestamps();

            // Relación con la tabla de listas
            $table->foreign('list_id')->references('id')->on('lists')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscribers');
    }
}
