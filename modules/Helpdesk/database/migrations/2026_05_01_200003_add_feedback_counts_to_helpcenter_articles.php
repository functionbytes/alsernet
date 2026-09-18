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

        $schema->table('helpdesk_helpcenter_articles', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_helpcenter_articles', 'helpful_count')) {
                $table->unsignedInteger('helpful_count')->default(0)->after('views_count');
            }
            if (! $schema->hasColumn('helpdesk_helpcenter_articles', 'unhelpful_count')) {
                $table->unsignedInteger('unhelpful_count')->default(0)->after('helpful_count');
            }
            if (! $schema->hasColumn('helpdesk_helpcenter_articles', 'tags')) {
                $table->json('tags')->nullable()->after('excerpt');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_helpcenter_articles', function (Blueprint $table) {
            $table->dropColumn(['helpful_count', 'unhelpful_count', 'tags']);
        });
    }
};
