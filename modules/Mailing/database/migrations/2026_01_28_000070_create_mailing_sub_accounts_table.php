<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_sub_accounts', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('sending_server_id');
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('api_key_id')->nullable();
            $table->string('api_key')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('customer_id')->references('id')->on('mailing_customers')->onDelete('cascade');
            $table->foreign('sending_server_id')->references('id')->on('mailing_sending_servers')->onDelete('cascade');

            $table->index('customer_id');
            $table->index('sending_server_id');

            // Foreign Keys
            $table->foreign('customer_id')
                ->references('id')
                ->on('mailing_customers')
                ->onDelete('cascade');
            $table->foreign('sending_server_id')
                ->references('id')
                ->on('mailing_sending_servers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_sub_accounts');
    }
};
