<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_ai_action_runs', 'user_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_ai_action_runs', function (Blueprint $table) {
            // Ejecuciones con source = agent: quién la lanzó y si verificó él la identidad.
            $table->unsignedBigInteger('user_id')->nullable()->after('conversation_id');
            $table->boolean('agent_verified')->default(false)->after('user_id');

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_action_runs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'agent_verified']);
        });
    }
};
