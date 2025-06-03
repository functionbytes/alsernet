<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReturnCommunicationsTable extends Migration
{
    public function up()
    {
        Schema::create('return_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->onDelete('cascade');
            $table->enum('type', ['email', 'sms', 'internal_note']);
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('content');
            $table->string('template_used')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('sent_by')->nullable();
            $table->json('metadata')->nullable(); // Para guardar info adicional
            $table->timestamps();

            $table->index(['return_id', 'type', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('return_communications');
    }
}
