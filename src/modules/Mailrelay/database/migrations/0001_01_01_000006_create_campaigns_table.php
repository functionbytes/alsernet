<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration
{
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->text('html_content');
            $table->text('text_content');
            $table->unsignedBigInteger('list_id');
            $table->enum('status', ['draft', 'sent', 'queued'])->default('draft');
            $table->timestamps();

            // Relación con la tabla de listas
            $table->foreign('list_id')->references('id')->on('lists')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaigns');
    }
}
