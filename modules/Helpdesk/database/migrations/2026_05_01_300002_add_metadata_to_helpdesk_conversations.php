<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table): void {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_conversations', 'metadata')) {
                $table->json('metadata')->nullable()->after('tags');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
