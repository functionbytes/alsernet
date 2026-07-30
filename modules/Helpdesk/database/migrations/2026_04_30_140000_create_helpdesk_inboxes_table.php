<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('helpdesk')->hasTable('helpdesk_inboxes')) {
            return;
        }

        Schema::connection('helpdesk')->create('helpdesk_inboxes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name', 120);
            // whatsapp | facebook | instagram | email | widget
            $table->string('channel_type', 32)->index();
            $table->boolean('is_active')->default(true)->index();

            // Credenciales / config específica del canal (encriptado en accessor del modelo)
            $table->json('credentials')->nullable();

            // Defaults para conversaciones que entren por este inbox
            $table->unsignedBigInteger('default_assignee_id')->nullable()->index();
            $table->foreignId('default_group_id')->nullable()
                ->constrained('helpdesk_groups')->nullOnDelete();

            // Comportamiento
            $table->boolean('greeting_enabled')->default(false);
            $table->text('greeting_message')->nullable();
            $table->boolean('working_hours_enabled')->default(false);
            $table->json('working_hours')->nullable();
            $table->text('out_of_office_message')->nullable();

            // Identidad visual en la UI
            $table->string('color', 16)->nullable();
            $table->string('icon', 64)->nullable();

            // Notas internas, no visible para clientes
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['channel_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_inboxes');
    }
};
