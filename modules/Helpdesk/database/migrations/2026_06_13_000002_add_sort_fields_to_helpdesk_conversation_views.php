<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_views', function (Blueprint $table) {
            $table->string('sort_by')->nullable()->after('filters');
            $table->string('sort_direction')->nullable()->default('desc')->after('sort_by');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_views', function (Blueprint $table) {
            $table->dropColumn(['sort_by', 'sort_direction']);
        });
    }
};
