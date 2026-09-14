<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La auto-asignación (listener/job en cola) crea registros de asignación sin
 * usuario autenticado: assigned_by pasa a ser nullable (NULL = "sistema").
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_assignments', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
        });

        Schema::connection($this->connection)->table('helpdesk_ticket_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_by')->nullable()->change();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_ticket_assignments', function (Blueprint $table) {
            $table->dropForeign(['assigned_by']);
        });

        Schema::connection($this->connection)->table('helpdesk_ticket_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_by')->nullable(false)->change();
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
