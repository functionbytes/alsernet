<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_channel_webs', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'cms_type')) {
                $table->string('cms_type', 30)->default('custom')->after('website_url');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'platform_integration_id')) {
                $table->unsignedBigInteger('platform_integration_id')->nullable()->after('cms_type');

                if (Schema::connection($this->connection)->hasTable('engagement_platform_integrations')) {
                    $table->foreign('platform_integration_id')
                        ->references('id')
                        ->on('engagement_platform_integrations')
                        ->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_channel_webs', function (Blueprint $table) {
            if (Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'platform_integration_id')) {
                if (Schema::connection($this->connection)->hasTable('engagement_platform_integrations')) {
                    try {
                        $table->dropForeign(['platform_integration_id']);
                    } catch (Throwable) {
                        // FK may not exist if Engagement was disabled at up() time.
                    }
                }
                $table->dropColumn('platform_integration_id');
            }

            if (Schema::connection($this->connection)->hasColumn('helpdesk_channel_webs', 'cms_type')) {
                $table->dropColumn('cms_type');
            }
        });
    }
};
