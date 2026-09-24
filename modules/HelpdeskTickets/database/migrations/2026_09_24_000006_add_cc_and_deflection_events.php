<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portal (24-sep-2026):
 * - cc_emails: correos en copia que el cliente añade al abrir el ticket; las
 *   respuestas del equipo les llegan también.
 * - helpdesk_ticket_deflection_events: medir la deflexión (artículos
 *   sugeridos mostrados / abiertos / ticket creado igualmente).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasColumn('helpdesk_tickets', 'cc_emails')) {
            $schema->table('helpdesk_tickets', function (Blueprint $table) {
                $table->json('cc_emails')->nullable();
            });
        }

        if (! $schema->hasTable('helpdesk_ticket_deflection_events')) {
            $schema->create('helpdesk_ticket_deflection_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('event', 16); // shown | clicked | created
                $table->unsignedBigInteger('article_id')->nullable();
                $table->unsignedBigInteger('ticket_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['event', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('helpdesk_ticket_deflection_events');

        if ($schema->hasColumn('helpdesk_tickets', 'cc_emails')) {
            $schema->table('helpdesk_tickets', fn (Blueprint $table) => $table->dropColumn('cc_emails'));
        }
    }
};
