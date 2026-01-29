<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_auto_triggers', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->unsignedInteger('subscriber_id')->nullable();
            $table->unsignedInteger('automation2_id');
            $table->text('data')->nullable();
            $table->text('executed_index')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('automation2_id')->references('id')->on('mailing_automation2s')->onDelete('cascade');
            $table->foreign('subscriber_id')->references('id')->on('mailing_subscribers')->onDelete('set null');

            $table->index('automation2_id');
            $table->index('subscriber_id');

            // Foreign Keys
            $table->foreign('automation2_id')
                ->references('id')
                ->on('mailing_automation2s')
                ->onDelete('cascade');
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('mailing_subscribers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_auto_triggers');
    }
};
