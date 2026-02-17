<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFieldsTable extends Migration
{
    public function up()
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del campo
            $table->enum('type', ['text', 'number', 'date', 'boolean'])->default('text'); // Tipo de campo
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_fields');
    }
}
