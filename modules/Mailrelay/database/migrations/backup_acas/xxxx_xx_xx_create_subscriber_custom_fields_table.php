<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriberCustomFieldsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriber_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('custom_field_id');
            $table->text('value'); // Valor del campo personalizado
            $table->timestamps();

            // Relación con suscriptores
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->onDelete('cascade');
            // Relación con campos personalizados
            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriber_custom_fields');
    }
}
