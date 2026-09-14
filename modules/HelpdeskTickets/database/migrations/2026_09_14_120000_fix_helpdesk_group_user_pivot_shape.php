<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `helpdesk_group_user` sobrevivió con la forma vieja del módulo Helpdesk
 * (helpdesk_group_id, role, assigned_at) en vez de la que TicketGroup::users()
 * / Group::users() / HasHelpdeskRelations::groups() esperan desde que se
 * unificó en un solo pivot compartido: group_id + conversation_priority
 * (ver 2025_12_29_020927_create_helpdesk_ticket_group_user_table).
 *
 * Las dos migraciones que crean esta tabla tienen guard hasTable(), así que
 * nunca la corrigen si ya existe con la forma antigua — por eso el listado de
 * grupos daba "Unknown column 'helpdesk_group_user.group_id'". Se recrea
 * porque está vacía (0 filas: verificado antes de escribir esta migración).
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('helpdesk_group_user')) {
            return;
        }

        if ($schema->hasColumn('helpdesk_group_user', 'group_id')) {
            return;
        }

        $schema->drop('helpdesk_group_user');

        $schema->create('helpdesk_group_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->string('conversation_priority')->default('primary');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('helpdesk_groups')->cascadeOnDelete();

            $table->unique(['group_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        // La forma anterior (helpdesk_group_id/role/assigned_at) era la
        // heredada de manager, ya vacía y desalineada con el código actual:
        // no tiene sentido reponerla. down() deja la tabla vacía con la
        // misma forma que crea up().
        Schema::connection($this->connection)->dropIfExists('helpdesk_group_user');
    }
};
