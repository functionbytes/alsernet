<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vistas guardadas de la bandeja "Emails enviados" — mismo esquema que
 * helpdesk_conversation_views (Modules\Helpdesk\Models\ConversationView),
 * clonado a propósito en vez de reutilizar esa tabla: los filtros de emails
 * (view/origin/tag/category/agent/from/to) no tienen nada que ver con los de
 * conversaciones, y mezclarlos ahí acoplaría dos módulos sin necesidad.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_ticket_mail_views', function (Blueprint $table) {
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
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_mail_views');
    }
};
