<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('helpdesk_broadcasts')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('helpdesk_customers')->cascadeOnDelete();
            $table->string('external_id')->nullable(); // external message id returned by Meta
            $table->string('status')->default('pending'); // pending|sent|delivered|read|failed
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('broadcast_id');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_broadcast_recipients');
    }
};
