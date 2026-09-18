<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vistas guardadas del log de emails — mismo esquema que
 * helpdesk_ticket_mail_views (Modules\HelpdeskTickets\Models\TicketMailView),
 * que a su vez clonó helpdesk_conversation_views (Modules\Helpdesk\Models\
 * ConversationView) — tercera vez que se usa este patrón en el proyecto,
 * ahora cross-módulo: 'filters' es un array genérico (module/status/search/
 * date_from/date_to/entity_type/entity_id), sin nada específico de tickets
 * (categoría/tags/agente no existen en EmailLog).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_log_views', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('filters')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_log_views');
    }
};
