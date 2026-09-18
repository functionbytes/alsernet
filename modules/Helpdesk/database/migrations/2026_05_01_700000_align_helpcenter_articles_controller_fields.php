<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_helpcenter_articles', function (Blueprint $table) {
            // Make category_id nullable — the pivot table handles section associations
            if (Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->change();
            }

            // Make content nullable — controller uses `body` column instead
            if (Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'content')) {
                $table->longText('content')->nullable()->change();
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'body')) {
                $table->longText('body')->nullable()->after('content');
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'description')) {
                $table->text('description')->nullable()->after('body');
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'position')) {
                $table->integer('position')->default(0)->after('description');
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'draft')) {
                $table->boolean('draft')->default(false)->after('position');
            }

            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'hide_from_structure')) {
                $table->boolean('hide_from_structure')->default(false)->after('draft');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_helpcenter_articles', function (Blueprint $table) {
            $table->dropColumn(['body', 'description', 'position', 'draft', 'hide_from_structure']);
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
        });
    }
};
