<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignFoldersTable extends Migration
{
    public function up()
    {
        Schema::create('campaign_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la carpeta
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_folders');
    }
}
