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

        if (! $schema->hasColumn('helpdesk_conversation_views', 'is_system')) {
            $schema->table('helpdesk_conversation_views', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('is_default');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_views', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
