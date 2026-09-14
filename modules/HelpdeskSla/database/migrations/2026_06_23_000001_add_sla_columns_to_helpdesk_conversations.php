<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('sla_policy_id')->nullable()->after('sla_warned_at');
            $table->timestamp('sla_first_response_due_at')->nullable()->after('sla_policy_id');
            $table->timestamp('sla_resolution_due_at')->nullable()->after('sla_first_response_due_at');
            $table->boolean('sla_first_response_breached')->default(false)->after('sla_resolution_due_at');
            $table->boolean('sla_resolution_breached')->default(false)->after('sla_first_response_breached');
            $table->timestamp('sla_paused_at')->nullable()->after('sla_resolution_breached');
            $table->integer('sla_paused_duration_minutes')->default(0)->after('sla_paused_at');

            $table->index('sla_policy_id');
            $table->index(['sla_first_response_breached', 'sla_first_response_due_at'], 'helpdesk_conv_sla_first_idx');
            $table->index(['sla_resolution_breached', 'sla_resolution_due_at'], 'helpdesk_conv_sla_resolution_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropIndex('helpdesk_conv_sla_first_idx');
            $table->dropIndex('helpdesk_conv_sla_resolution_idx');
            $table->dropIndex(['sla_policy_id']);
            $table->dropColumn([
                'sla_policy_id',
                'sla_first_response_due_at',
                'sla_resolution_due_at',
                'sla_first_response_breached',
                'sla_resolution_breached',
                'sla_paused_at',
                'sla_paused_duration_minutes',
            ]);
        });
    }
};
