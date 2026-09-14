<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_livestream_events')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_livestream_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedInteger('batch_index')->default(0);
            $table->longText('events');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('helpdesk_conversations')
                ->cascadeOnDelete();

            $table->index(['conversation_id', 'created_at'], 'hle_conv_created_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_livestream_events');
    }
};
