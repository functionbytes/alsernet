<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversation_statuses', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
