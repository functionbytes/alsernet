<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryMethodsTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_methods', function (Blueprint $table) {
            $table->id();
            $table->string('method_type'); // Ejemplo: 'email', 'sms', 'push'
            $table->text('configuration')->nullable(); // Configuración para el método
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_methods');
    }
}
