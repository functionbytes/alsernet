<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_sla_policies', function (Blueprint $table) {
            $table->string('uid')->nullable()->unique()->after('id');
            $table->string('key')->nullable()->unique()->after('uid');
            $table->string('priority')->nullable()->after('description');
            $table->integer('first_response_time_hours')->nullable()->after('priority');
            $table->integer('next_response_time_hours')->nullable()->after('first_response_time_hours');
            $table->integer('resolution_time_hours')->nullable()->after('next_response_time_hours');
            $table->integer('first_response_escalation_hours')->nullable()->after('resolution_time_hours');
            $table->integer('next_response_escalation_hours')->nullable()->after('first_response_escalation_hours');
            $table->integer('resolution_escalation_hours')->nullable()->after('next_response_escalation_hours');
            $table->boolean('escalate_to_manager')->default(false)->after('resolution_escalation_hours');
            $table->boolean('send_escalation_email')->default(true)->after('escalate_to_manager');
            $table->json('applicable_status_ids')->nullable()->after('send_escalation_email');
            $table->boolean('is_active')->default(true)->after('applicable_status_ids');
            $table->integer('position')->default(0)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_sla_policies', function (Blueprint $table) {
            $table->dropColumn([
                'uid', 'key', 'priority',
                'first_response_time_hours', 'next_response_time_hours', 'resolution_time_hours',
                'first_response_escalation_hours', 'next_response_escalation_hours', 'resolution_escalation_hours',
                'escalate_to_manager', 'send_escalation_email', 'applicable_status_ids',
                'is_active', 'position',
            ]);
        });
    }
};
