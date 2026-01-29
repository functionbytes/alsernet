<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_plans_email_verification_servers', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('server_id');
            $table->unsignedInteger('plan_id');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('plan_id')->references('id')->on('mailing_plans')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('mailing_email_verification_servers')->onDelete('cascade');

            $table->index('plan_id');
            $table->index('server_id');

            // Foreign Keys
            $table->foreign('plan_id')
                ->references('id')
                ->on('mailing_plans')
                ->onDelete('cascade');
            $table->foreign('server_id')
                ->references('id')
                ->on('mailing_email_verification_servers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_plans_email_verification_servers');
    }
};
