<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `ChangeStatusAction::resolveStatus()` distingue 'resolved'/'closed'/
 * 'pending' buscando primero por slug/name exacto (ver el cambio en esa
 * clase). En esta instalacion solo el estado "Open" tiene slug real; el
 * resto (Nuevo/Activo/Esperando/Resuelto/Cerrado/Archivado) se sembraron
 * sin slug, asi que 'resolved' y 'closed' caian los dos en el mismo
 * heuristico generico (cualquier estado is_open=false, el primero por
 * order) y 'pending' no resolvia a nada.
 *
 * Solo se rellena el slug donde esta vacio y el nombre coincide
 * exactamente: no se toca ninguna fila que ya tenga un slug propio (podria
 * ser una personalizacion deliberada) ni se crean estados nuevos.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    /** name real → slug que espera ChangeStatusAction::resolveStatus(). */
    private const SLUGS = [
        'Esperando' => 'pending',
        'Resuelto' => 'resolved',
        'Cerrado' => 'closed',
    ];

    public function up(): void
    {
        foreach (self::SLUGS as $name => $slug) {
            DB::connection($this->connection)
                ->table('helpdesk_conversation_statuses')
                ->where('name', $name)
                ->where(function ($q) {
                    $q->whereNull('slug')->orWhere('slug', '');
                })
                ->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        foreach (self::SLUGS as $name => $slug) {
            DB::connection($this->connection)
                ->table('helpdesk_conversation_statuses')
                ->where('name', $name)
                ->where('slug', $slug)
                ->update(['slug' => null]);
        }
    }
};
