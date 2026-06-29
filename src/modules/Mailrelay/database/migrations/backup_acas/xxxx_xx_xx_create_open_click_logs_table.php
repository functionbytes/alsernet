<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenClickLogsTable extends Migration
{
    public function up()
    {
        Schema::create('open_click_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id'); // Suscriptor que realizó la acción
            $table->unsignedBigInteger('campaign_id'); // Campaña en la que se realizó la acción
            $table->enum('action', ['opened', 'clicked']); // Acción (apertura o clic)
            $table->string('url')->nullable(); // URL si se trata de un clic
            $table->timestamps();

            // Relaciones con suscriptores y campañas
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('open_click_logs');
    }
}
