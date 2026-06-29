<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_ticket_followups')) {
            return;
        }

        $schema->create('helpdesk_ticket_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamp('scheduled_at')->index();
            $table->text('note')->nullable();
            $table->boolean('is_sent')->default(false)->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_followups');
    }
};
