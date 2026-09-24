<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    /**
     * Notas internas del contacto con autor y fecha (Contactos 360, bloque
     * "Notas internas · Añadir"). Sustituye a la columna única
     * helpdesk_customers.internal_notes, que se sigue mostrando como nota
     * heredada. user_id sin foreign key: `users` vive en otra conexión.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_customer_notes')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('helpdesk_customers')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_customer_notes');
    }
};
