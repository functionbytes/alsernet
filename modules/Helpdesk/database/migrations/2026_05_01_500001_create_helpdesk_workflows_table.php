<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type'); // conversation_created|message_received|conversation_closed|csat_answered|tag_added|sla_breach|manual
            $table->json('trigger_config')->nullable(); // {channel?, tag_id?, etc.}
            $table->json('nodes')->nullable(); // [{id, type, config, next}]
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('total_runs')->default(0);
            $table->timestamps();

            $table->index('trigger_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_workflows');
    }
};
