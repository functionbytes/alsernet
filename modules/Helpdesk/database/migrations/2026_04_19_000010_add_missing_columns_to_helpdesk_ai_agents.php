<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Reconcile the table with the columns the AiAgent model already expects
     * (provider, model, personality, status, backups, metadata, enabled_at,
     * deleted_at). All additions are conditional so the migration is idempotent
     * across environments whose schema may partially exist.
     */
    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_agents', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', 'provider')) {
                $table->string('provider')->nullable()->after('description');
                $table->index('provider');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', 'model')) {
                $table->string('model')->nullable()->after('provider');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', 'personality')) {
                $table->text('personality')->nullable()->after('model');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', 'status')) {
                $table->string('status', 32)->default('inactive')->after('personality');
                $table->index('status');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', 'backups')) {
                $table->json('backups')->nullable()->after('configuration');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', 'metadata')) {
                $table->json('metadata')->nullable()->after('backups');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', 'enabled_at')) {
                $table->timestamp('enabled_at')->nullable()->after('active');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ai_agents', function (Blueprint $table) {
            $columns = ['provider', 'model', 'personality', 'status', 'backups', 'metadata', 'enabled_at', 'deleted_at'];

            foreach ($columns as $column) {
                if (Schema::connection($this->connection)->hasColumn('helpdesk_ai_agents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
