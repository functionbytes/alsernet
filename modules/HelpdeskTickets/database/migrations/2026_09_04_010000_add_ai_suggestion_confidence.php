<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modal 27 "Etiquetado automático": el mockup pide un % de confianza por
 * sugerencia -- ai_suggested_category_id/ai_suggested_priority existían pero
 * sin ningún valor de confianza asociado.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $s = Schema::connection($this->connection);

        if ($s->hasTable('helpdesk_tickets') && ! $s->hasColumn('helpdesk_tickets', 'ai_suggested_category_confidence')) {
            $s->table('helpdesk_tickets', function (Blueprint $t) {
                $t->decimal('ai_suggested_category_confidence', 3, 2)->nullable()->after('ai_suggested_category_id');
                $t->decimal('ai_suggested_priority_confidence', 3, 2)->nullable()->after('ai_suggested_priority');
            });
        }
    }

    public function down(): void {}
};
