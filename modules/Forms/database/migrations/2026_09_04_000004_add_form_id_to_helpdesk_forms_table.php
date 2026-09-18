<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula un form_key de alsernetforms con el formulario del constructor que lo
 * define.
 *
 * `helpdesk_forms` (modelo AlsernetForm) ya es el registro administrable
 * form_key → categoría de ticket, con las 19 filas reales del sitio. Colgando
 * aquí el `form_id`, la categoría del ticket que abrirá el envío sale gratis:
 * no hay que duplicar ese mapeo en la definición publicada.
 *
 * Sin FK: `forms` vive en la conexión por defecto y esta tabla en 'helpdesk',
 * así que la integridad no se puede delegar al motor.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasColumn('helpdesk_forms', 'form_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_forms', function (Blueprint $table) {
            $table->unsignedBigInteger('form_id')->nullable()->after('form_key')
                ->comment('Modules\Forms\Models\Form que define este formulario (conexión por defecto)');
            $table->index('form_id');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('helpdesk_forms', 'form_id')) {
            return;
        }

        Schema::connection($this->connection)->table('helpdesk_forms', function (Blueprint $table) {
            $table->dropIndex(['form_id']);
            $table->dropColumn('form_id');
        });
    }
};
