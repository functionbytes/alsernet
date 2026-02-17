<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbackSurveysTable extends Migration
{
    public function up()
    {
        Schema::create('feedback_surveys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->text('survey_content'); // Contenido de la encuesta
            $table->timestamps();

            // Relación con la tabla de campañas
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('feedback_surveys');
    }
}
