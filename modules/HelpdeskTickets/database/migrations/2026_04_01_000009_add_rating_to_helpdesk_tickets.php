<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'rating')) {
                $table->tinyInteger('rating')->nullable()->after('is_archived');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'rating_comment')) {
                $table->string('rating_comment', 500)->nullable()->after('rating');
            }

            if (! Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'rated_at')) {
                $table->timestamp('rated_at')->nullable()->after('rating_comment');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            $table->dropColumn(['rating', 'rating_comment', 'rated_at']);
        });
    }
};
