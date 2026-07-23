<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CSAT con razón: cuando el cliente valora bajo, captura el *por qué* de la
 * insatisfacción (razón estructurada de una lista configurable), como
 * Zendesk/Freshdesk — no solo la estrella y un comentario libre.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            if (! Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'rating_reason')) {
                $table->string('rating_reason')->nullable()->after('rating_comment');
                $table->index('rating_reason', 'helpdesk_tickets_rating_reason_index');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_tickets', function (Blueprint $table) {
            if (Schema::connection($this->connection)->hasColumn('helpdesk_tickets', 'rating_reason')) {
                $table->dropIndex('helpdesk_tickets_rating_reason_index');
                $table->dropColumn('rating_reason');
            }
        });
    }
};
