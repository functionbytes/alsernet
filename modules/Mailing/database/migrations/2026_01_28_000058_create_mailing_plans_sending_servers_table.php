<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_plans_sending_servers', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('sending_server_id');
            $table->unsignedInteger('plan_id');
            $table->integer('fitness');
            $table->boolean('is_primary')->default(0);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('plan_id')->references('id')->on('mailing_plans')->onDelete('cascade');
            $table->foreign('sending_server_id')->references('id')->on('mailing_sending_servers')->onDelete('cascade');

            $table->index('plan_id');
            $table->index('sending_server_id');

            // Foreign Keys
            $table->foreign('plan_id')
                ->references('id')
                ->on('mailing_plans')
                ->onDelete('cascade');
            $table->foreign('sending_server_id')
                ->references('id')
                ->on('mailing_sending_servers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_plans_sending_servers');
    }
};
