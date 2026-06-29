<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('engagement_platform_integrations', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('engagement_platform_integrations', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('engagement_platform_integrations', function (Blueprint $table) {
            if (Schema::connection('helpdesk')->hasColumn('engagement_platform_integrations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
