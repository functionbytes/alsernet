<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $s = Schema::connection($this->connection);

        if ($s->hasTable('helpdesk_ticket_items') && ! $s->hasColumn('helpdesk_ticket_items', 'sentiment')) {
            $s->table('helpdesk_ticket_items', function (Blueprint $t) {
                $t->string('sentiment', 16)->nullable()->index();
                $t->decimal('sentiment_score', 3, 2)->nullable();
            });
        }

        if ($s->hasTable('helpdesk_tickets') && ! $s->hasColumn('helpdesk_tickets', 'ai_suggested_category_id')) {
            $s->table('helpdesk_tickets', function (Blueprint $t) {
                $t->unsignedBigInteger('ai_suggested_category_id')->nullable();
                $t->string('ai_suggested_priority', 16)->nullable();
                $t->decimal('customer_sentiment_avg', 3, 2)->nullable();
            });
        }
    }

    public function down(): void {}
};
