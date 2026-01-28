<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBulkEmailSendingTable extends Migration
{
    public function up()
    {
        Schema::create('bulk_email_sending', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('queued'); // Estado del envío (pendiente, enviado, fallido)
            $table->unsignedBigInteger('bulk_email_id'); // ID del lote de correos
            $table->timestamps();

            // Relación con los lotes de correos
            $table->foreign('bulk_email_id')->references('id')->on('bulk_emails')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bulk_email_sending');
    }
}
