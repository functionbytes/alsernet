<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignStatusTable extends Migration
{
    public function up()
    {
        Schema::create('campaign_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->enum('status', ['pending', 'sent', 'failed', 'opened']);
            $table->timestamp('changed_at');
            $table->timestamps();

            // Relación con la tabla de campañas
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_status');
    }
}
