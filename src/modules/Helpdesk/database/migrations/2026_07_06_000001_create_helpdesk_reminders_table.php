<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_reminders', function (Blueprint $table) {
            $table->id();
            // El agente dueño del recordatorio (users vive en otra conexión → sin FK).
            $table->unsignedBigInteger('user_id');
            $table->foreignId('conversation_id')->nullable()
                ->constrained('helpdesk_conversations')->nullOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->timestamp('remind_at');
            $table->boolean('email_notify')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Cola del due-scanner y listado por agente.
            $table->index(['user_id', 'completed_at']);
            $table->index(['remind_at', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_reminders');
    }
};
