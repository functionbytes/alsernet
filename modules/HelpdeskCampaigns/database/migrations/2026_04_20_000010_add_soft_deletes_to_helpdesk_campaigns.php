<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    private array $tables = [
        'helpdesk_campaigns',
        'helpdesk_campaign_impressions',
        'helpdesk_campaign_templates',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::connection($this->connection)->hasTable($table)
                && ! Schema::connection($this->connection)->hasColumn($table, 'deleted_at')) {
                Schema::connection($this->connection)->table($table, function (Blueprint $t) {
                    $t->softDeletes();
                });
            }
        }
    }

    public function down(): void {}
};
