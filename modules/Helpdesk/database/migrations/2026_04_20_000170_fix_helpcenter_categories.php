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

        if (! $schema->hasTable('helpdesk_helpcenter_categories')) {
            return;
        }

        $schema->table('helpdesk_helpcenter_categories', function (Blueprint $t) use ($schema) {
            if (! $schema->hasColumn('helpdesk_helpcenter_categories', 'position')) {
                $t->integer('position')->default(0)->index();
            }
            if (! $schema->hasColumn('helpdesk_helpcenter_categories', 'parent_id')) {
                $t->unsignedBigInteger('parent_id')->nullable()->index();
            }
            if (! $schema->hasColumn('helpdesk_helpcenter_categories', 'is_section')) {
                $t->boolean('is_section')->default(false)->index();
            }
            if (! $schema->hasColumn('helpdesk_helpcenter_categories', 'deleted_at')) {
                $t->softDeletes();
            }
        });
    }

    public function down(): void {}
};
