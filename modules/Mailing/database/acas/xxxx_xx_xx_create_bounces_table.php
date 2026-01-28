<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBouncesTable extends Migration
{
    public function up()
    {
        Schema::create('bounces', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->unsignedBigInteger('campaign_id');
            $table->string('reason')->nullable(); // Razón del rebote
            $table->timestamp('bounced_at')->nullable();
            $table->timestamps();

            // Relación con campañas
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bounces');
    }
}
