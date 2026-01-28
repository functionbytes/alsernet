<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('list_id');
            $table->timestamps();

            // Relación con la tabla de listas
            $table->foreign('list_id')->references('id')->on('lists')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('groups');
    }
}
