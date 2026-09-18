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

        if ($schema->hasTable('helpdesk_webhooks') && ! $schema->hasColumn('helpdesk_webhooks', 'integration_type')) {
            $schema->table('helpdesk_webhooks', function (Blueprint $table) {
                $table->string('integration_type', 16)->default('generic')->index()->after('url');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_webhooks') && $schema->hasColumn('helpdesk_webhooks', 'integration_type')) {
            $schema->table('helpdesk_webhooks', function (Blueprint $table) {
                $table->dropColumn('integration_type');
            });
        }
    }
};
