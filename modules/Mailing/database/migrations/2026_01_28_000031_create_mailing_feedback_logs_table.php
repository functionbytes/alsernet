<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_feedback_logs', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('runtime_message_id')->nullable();
            $table->string('message_id')->nullable();
            $table->string('feedback_type');
            $table->text('raw_feedback_content');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_feedback_logs');
    }
};
