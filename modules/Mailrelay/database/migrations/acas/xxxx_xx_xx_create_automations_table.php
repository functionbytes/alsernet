<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomationsTable extends Migration
{
    public function up()
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('trigger');
            $table->text('action');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('automations');
    }
}
