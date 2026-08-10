<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sustituye el mapeo hardcodeado en PHP (antes: FormsReportController::CATEGORY_SLUGS,
 * y del lado alsernetforms/PrestaShop FormCategoryRegistry) por filas administrables
 * desde el panel. `form_key` es la clave que envía alsernetforms en el payload
 * ('type'); category_id apunta a la TicketCategory que recibe los tickets de
 * ese formulario.
 *
 * Vive en la conexión 'helpdesk' (no la default) porque se relaciona
 * directamente con helpdesk_ticket_categories y helpdesk_tickets, ambas ahí.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('form_key')->unique();
            $table->foreignId('category_id')->nullable()
                ->constrained('helpdesk_ticket_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_forms');
    }
};
