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

        $schema->table('helpdesk_categories', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_categories', 'position')) {
                $table->integer('position')->default(0)->after('is_active');
            }
        });

        $schema->table('helpdesk_conversation_statuses', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_conversation_statuses', 'uid')) {
                $table->string('uid')->nullable()->unique()->after('id');
            }
            if (! $schema->hasColumn('helpdesk_conversation_statuses', 'position')) {
                $table->integer('position')->default(0)->after('order');
            }
        });

        $schema->table('helpdesk_groups', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('helpdesk_groups', 'uid')) {
                $table->string('uid')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('helpdesk_categories', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        $schema->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->dropColumn(['uid', 'position']);
        });

        $schema->table('helpdesk_groups', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
