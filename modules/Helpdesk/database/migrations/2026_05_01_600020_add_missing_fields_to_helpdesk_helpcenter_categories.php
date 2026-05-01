<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_helpcenter_categories', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_categories', 'icon')) {
                $table->string('icon')->nullable()->after('description');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_categories', 'image')) {
                $table->string('image')->nullable()->after('icon');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_categories', 'visible_to_role')) {
                $table->string('visible_to_role')->nullable()->after('image');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_categories', 'managed_by_role')) {
                $table->string('managed_by_role')->nullable()->after('visible_to_role');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_helpcenter_categories', function (Blueprint $table) {
            $table->dropColumn(['icon', 'image', 'visible_to_role', 'managed_by_role']);
        });
    }
};
