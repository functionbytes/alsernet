<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_customer_group_sending_servers', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->integer('sending_server_id');
            $table->string('customer_group_id');
            $table->integer('fitness');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_customer_group_sending_servers');
    }
};
