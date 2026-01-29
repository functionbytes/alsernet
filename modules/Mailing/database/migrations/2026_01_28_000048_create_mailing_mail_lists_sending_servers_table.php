<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_mail_lists_sending_servers', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('sending_server_id');
            $table->unsignedInteger('mail_list_id');
            $table->integer('fitness');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('mail_list_id')->references('id')->on('mailing_mail_lists')->onDelete('cascade');
            $table->foreign('sending_server_id')->references('id')->on('mailing_sending_servers')->onDelete('cascade');

            $table->index('mail_list_id');
            $table->index('sending_server_id');

            // Foreign Keys
            $table->foreign('mail_list_id')
                ->references('id')
                ->on('mailing_mail_lists')
                ->onDelete('cascade');
            $table->foreign('sending_server_id')
                ->references('id')
                ->on('mailing_sending_servers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_mail_lists_sending_servers');
    }
};
