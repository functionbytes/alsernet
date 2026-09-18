<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retira las vistas guardadas propias de la bandeja global "Emails enviados"
 * (Modules\HelpdeskTickets\Models\TicketMailView / TicketMailViewsController)
 * — la bandeja se retiró (ver plan de unificación con HelpdeskEmailActivity), y
 * su equivalente genérico cross-módulo vive ahora en
 * Modules\HelpdeskEmailActivity\Models\EmailLogView (tabla email_log_views).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_ticket_mail_views');
    }

    public function down(): void
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
};
