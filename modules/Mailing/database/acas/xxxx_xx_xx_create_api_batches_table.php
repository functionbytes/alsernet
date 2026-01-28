<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiBatchesTable extends Migration
{
    public function up()
    {
        Schema::create('api_batches', function (Blueprint $table) {
            $table->id();
            $table->json('batch_data'); // Datos del lote en formato JSON
            $table->timestamp('executed_at')->nullable(); // Fecha de ejecución del lote
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_batches');
    }
}
