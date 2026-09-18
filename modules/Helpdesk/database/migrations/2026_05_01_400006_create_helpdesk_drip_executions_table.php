<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_drip_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('helpdesk_drip_campaigns')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('helpdesk_customers')->cascadeOnDelete();
            $table->unsignedInteger('current_step')->default(0);
            $table->string('status')->default('active'); // active|paused|completed|cancelled
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Prevent duplicate campaign enrollments per customer
            $table->unique(['campaign_id', 'customer_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_drip_executions');
    }
};
