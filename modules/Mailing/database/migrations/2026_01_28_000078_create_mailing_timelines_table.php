<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_timelines', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->char('uid', 36)->unique();
            $table->unsignedInteger('automation2_id');
            $table->unsignedInteger('subscriber_id');
            $table->unsignedInteger('auto_trigger_id');
            $table->text('activity');
            $table->string('activity_type');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('automation2_id')->references('id')->on('mailing_automation2s')->onDelete('cascade');
            $table->foreign('auto_trigger_id')->references('id')->on('mailing_auto_triggers')->onDelete('cascade');
            $table->foreign('subscriber_id')->references('id')->on('mailing_subscribers')->onDelete('cascade');

            $table->index('automation2_id');
            $table->index('auto_trigger_id');
            $table->index('subscriber_id');

            // Foreign Keys
            $table->foreign('auto_trigger_id')
                ->references('id')
                ->on('mailing_auto_triggers')
                ->onDelete('cascade');
            $table->foreign('automation2_id')
                ->references('id')
                ->on('mailing_automation2s')
                ->onDelete('cascade');
            $table->foreign('subscriber_id')
                ->references('id')
                ->on('mailing_subscribers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_timelines');
    }
};
