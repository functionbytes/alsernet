<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        // Disparadores proactivos del widget (live commerce, fase 5): las
        // condiciones de Oct8ne (tiempo, páginas, productos vistos, URL,
        // idioma, franja) + valor de carrito; se evalúan en el navegador.
        if (! Schema::connection($this->connection)->hasTable('helpdesk_widget_triggers')) {
            Schema::connection($this->connection)->create('helpdesk_widget_triggers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('web_id')->index();
                $table->string('name', 120);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('priority')->default(50);
                $table->string('match', 3)->default('all'); // all | any
                $table->json('conditions');
                $table->string('action', 20); // open_chat | message
                $table->text('message')->nullable();
                $table->string('frequency', 20)->default('once_visitor');
                $table->timestamps();
                $table->index(['web_id', 'is_active', 'priority']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_widget_triggers');
    }
};
