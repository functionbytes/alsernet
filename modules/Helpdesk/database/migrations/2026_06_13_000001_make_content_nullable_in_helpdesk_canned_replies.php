<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_canned_replies', function (Blueprint $table) {
            // Legacy "content" column is NOT NULL with no default. The application
            // uses "body"/"html_body" instead; inserting via CannedReply::create()
            // fails in strict mode because "content" is never supplied.
            // Making it nullable removes the constraint without dropping the column.
            $table->text('content')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Before reverting, backfill to avoid NOT NULL violations on rollback.
        DB::connection($this->connection)
            ->table('helpdesk_canned_replies')
            ->whereNull('content')
            ->update(['content' => '']);

        Schema::connection($this->connection)->table('helpdesk_canned_replies', function (Blueprint $table) {
            $table->text('content')->nullable(false)->default('')->change();
        });
    }
};
