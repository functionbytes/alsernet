<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnsubscribeEventsTable extends Migration
{
    public function up()
    {
        Schema::create('unsubscribe_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('campaign_id');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            // Relación con suscriptores y campañas
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('unsubscribe_events');
    }
}
