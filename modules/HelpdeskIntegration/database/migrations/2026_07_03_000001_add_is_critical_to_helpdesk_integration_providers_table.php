<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_integration_providers', function (Blueprint $table) {
            $table->boolean('is_critical')->default(false)->after('is_linkable');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_integration_providers', function (Blueprint $table) {
            $table->dropColumn('is_critical');
        });
    }
};
