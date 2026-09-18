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

        if ($schema->hasTable('helpdesk_helpcenter_articles') && ! $schema->hasColumn('helpdesk_helpcenter_articles', 'deleted_at')) {
            $schema->table('helpdesk_helpcenter_articles', function (Blueprint $t) {
                $t->softDeletes();
            });
        }

        if ($schema->hasTable('helpdesk_helpcenter_articles') && ! $schema->hasColumn('helpdesk_helpcenter_articles', 'is_published')) {
            $schema->table('helpdesk_helpcenter_articles', function (Blueprint $t) {
                $t->boolean('is_published')->default(false)->index();
            });
        }

        if ($schema->hasTable('helpdesk_helpcenter_articles') && ! $schema->hasColumn('helpdesk_helpcenter_articles', 'published_at')) {
            $schema->table('helpdesk_helpcenter_articles', function (Blueprint $t) {
                $t->timestamp('published_at')->nullable();
            });
        }

        if ($schema->hasTable('helpdesk_helpcenter_articles') && ! $schema->hasColumn('helpdesk_helpcenter_articles', 'meta_description')) {
            $schema->table('helpdesk_helpcenter_articles', function (Blueprint $t) {
                $t->text('meta_description')->nullable();
            });
        }
    }

    public function down(): void {}
};
