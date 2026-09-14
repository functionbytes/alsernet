<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `2026_04_20_000001_align_helpdesk_tickets_schema` intended `customer_id` and
 * `subject` to become nullable (StoreTicketRequest/UpdateTicketRequest already
 * validate both as optional), but its `addMissingColumns()` only adds columns
 * that don't exist yet. Since both columns already existed as NOT NULL from
 * the original `create_helpdesk_tickets_table` migration, that intent never
 * applied. This corrects the existing columns in place (nullable only, no
 * other attribute changes — type/length/default all preserved).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->string('subject')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
            $table->string('subject')->nullable(false)->change();
        });
    }
};
