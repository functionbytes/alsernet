<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ConversationFilter::applyCustomerSearch() buscaba nombre/email de
 * helpdesk_customers con LIKE '%term%': el comodín inicial impide usar
 * cualquier índice B-tree y fuerza un escaneo completo de la tabla en cada
 * búsqueda del inbox. Mismo patrón que el FULLTEXT ya usado para `subject`
 * (2026_05_06_300000_add_fulltext_to_helpdesk_search.php): índice compuesto
 * sobre (name, email) para MATCH ... AGAINST en boolean mode.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->fullText(['name', 'email'], 'helpdesk_customers_name_email_fulltext');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_customers', function (Blueprint $table) {
            $table->dropFullText('helpdesk_customers_name_email_fulltext');
        });
    }
};
