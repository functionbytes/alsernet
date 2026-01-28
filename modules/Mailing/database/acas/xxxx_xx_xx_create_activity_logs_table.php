<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('activity');
            $table->unsignedBigInteger('campaign_id');
            $table->timestamps();

            // Relación con la tabla de campañas
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
}
