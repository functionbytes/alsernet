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

        if (! $schema->hasTable('helpdesk_conversation_views')) {
            return;
        }

        $schema->table('helpdesk_conversation_views', function (Blueprint $t) use ($schema) {
            if (! $schema->hasColumn('helpdesk_conversation_views', 'name')) {
                $t->string('name')->nullable();
            }
            if (! $schema->hasColumn('helpdesk_conversation_views', 'slug')) {
                $t->string('slug')->nullable()->index();
            }
            if (! $schema->hasColumn('helpdesk_conversation_views', 'description')) {
                $t->text('description')->nullable();
            }
            if (! $schema->hasColumn('helpdesk_conversation_views', 'filters')) {
                $t->json('filters')->nullable();
            }
            if (! $schema->hasColumn('helpdesk_conversation_views', 'is_public')) {
                $t->boolean('is_public')->default(false)->index();
            }
            if (! $schema->hasColumn('helpdesk_conversation_views', 'is_default')) {
                $t->boolean('is_default')->default(false)->index();
            }
            if (! $schema->hasColumn('helpdesk_conversation_views', 'order')) {
                $t->integer('order')->default(0)->index();
            }
        });
    }

    public function down(): void {}
};
