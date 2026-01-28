<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBulkEmailsTable extends Migration
{
    public function up()
    {
        Schema::create('bulk_emails', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('html_content');
            $table->text('text_content');
            $table->json('list_ids'); // Lista de listas de correo asociadas
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bulk_emails');
    }
}
