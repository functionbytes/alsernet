<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResponseLogsTable extends Migration
{
    public function up()
    {
        Schema::create('response_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('campaign_id');
            $table->enum('response_type', ['open', 'click', 'bounce', 'spam']);
            $table->string('url')->nullable(); // URL de clic si es relevante
            $table->timestamps();

            // Relaciones con suscriptores y campañas
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('response_logs');
    }
}
