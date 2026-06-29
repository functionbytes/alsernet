<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_automation_triggers_log', function (Blueprint $table) {
            $table->id();
            $table->string('trigger_type', 64);
            $table->foreignId('store_id')->nullable()->constrained('remarketing_stores')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('remarketing_customers')->nullOnDelete();
            $table->json('context')->nullable();
            $table->boolean('email_sent')->default(false);
            $table->string('skip_reason', 128)->nullable();
            $table->timestamp('triggered_at');

            $table->index(['trigger_type', 'triggered_at'], 'rmkt_atl_type_triggered_idx');
            $table->index(['customer_id', 'trigger_type'], 'rmkt_atl_customer_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_automation_triggers_log');
    }
};
