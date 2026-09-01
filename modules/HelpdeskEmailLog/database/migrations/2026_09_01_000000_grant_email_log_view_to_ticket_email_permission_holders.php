<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Backfill de permisos tras el cambio de 'permission' del ítem de menú
 * "Emails enviados" (HelpdeskTicketsServiceProvider::registerMenus()) de
 * 'helpdesk.tickets.emails.view' a 'helpdeskemaillog.view'.
 *
 * La ruta 'manager.helpdesk.tickets.emails.index' hoy es solo un redirect
 * hacia helpdeskemaillog.index (TicketMailsController::index()), y la
 * autorización real la impone el destino
 * (EmailLogController::index() -> authorize('viewAny', EmailLog::class) ->
 * EmailLogPolicy::viewAny(), que exige 'helpdeskemaillog.view'). Cualquier
 * rol o usuario que ya tuviera 'helpdesk.tickets.emails.view' pero NO
 * 'helpdeskemaillog.view' se quedaría, tras ese cambio de menú, viendo un
 * ítem que ahora directamente no se lista (correcto, ya no tiene el permiso
 * que el menú exige) — pero si ya venía navegando la URL de memoria, o si el
 * cambio de menú AÚN NO se ha desplegado en ese entorno, seguía llevándose
 * un 403 al llegar al redirect. Esta migración cierra esa brecha de acceso
 * para quien ya contaba con 'helpdesk.tickets.emails.view':
 *
 *  (a) rol 'super-settings': ya tiene 'helpdesk.tickets.emails.view' (se lo
 *      da HelpdeskTicketsPermissionsSeeder), pero HelpdeskEmailLogPermissionsSeeder
 *      solo asignó 'helpdeskemaillog.view' a ['admin', 'super-admin',
 *      'super-administrador'] — 'super-settings' quedó fuera. Se lo damos
 *      aquí si el rol existe y ya tiene el permiso viejo.
 *
 *  (b) usuarios con 'helpdesk.tickets.emails.view' asignado DIRECTAMENTE
 *      (no heredado de un rol): se les da 'helpdeskemaillog.view' también
 *      de forma directa. Deliberadamente NO se usa el scope
 *      User::permission() de Spatie para esto — ese scope hace
 *      whereHas('permissions', ...) OR whereHas('roles', ...), es decir
 *      incluye también a quien lo tiene solo vía rol (p. ej. cualquier
 *      super-admin/super-settings), y eso ya se resuelve por separado (el
 *      caso rol en (a), y super-admin ya tenía 'helpdeskemaillog.view' de
 *      antes). Filtrar por la relación morphToMany `permissions()` (tabla
 *      model_has_permissions) es lo que de verdad aísla "asignado
 *      directamente al usuario, no vía rol".
 *
 * Idempotente: se puede correr más de una vez sin duplicar nada
 * (givePermissionTo() de Spatie ya es un syncWithoutDetaching internamente,
 * y además comprobamos hasPermissionTo() antes de tocar cada fila).
 */
return new class extends Migration
{
    private const OLD_PERMISSION = 'helpdesk.tickets.emails.view';

    private const NEW_PERMISSION = 'helpdeskemaillog.view';

    private const NEW_PERMISSION_GUARD = 'web';

    public function up(): void
    {
        $rolesUpdated = 0;
        $usersUpdated = 0;

        try {
            // Se crea aquí (no solo se busca) por si esta migración corriera
            // en un entorno donde HelpdeskEmailLog todavía no sembró sus
            // permisos — findOrCreate() no falla si ya existe.
            $newPermission = Permission::findOrCreate(self::NEW_PERMISSION, self::NEW_PERMISSION_GUARD);

            // (a) Rol 'super-settings'.
            $role = Role::where('name', 'super-settings')->first();

            if ($role && $role->hasPermissionTo(self::OLD_PERMISSION) && ! $role->hasPermissionTo(self::NEW_PERMISSION)) {
                $role->givePermissionTo($newPermission);
                $rolesUpdated++;
            }

            // (b) Usuarios con el permiso viejo asignado DIRECTAMENTE (no vía rol).
            $userModel = User::class;

            $users = $userModel::whereHas('permissions', function ($query) {
                $query->where('name', self::OLD_PERMISSION);
            })->get();

            foreach ($users as $user) {
                if (! $user->hasPermissionTo(self::NEW_PERMISSION)) {
                    $user->givePermissionTo($newPermission);
                    $usersUpdated++;
                }
            }

            Log::info('[HelpdeskEmailLog] Backfill de helpdeskemaillog.view completado', [
                'roles_updated' => $rolesUpdated,
                'users_updated' => $usersUpdated,
            ]);
        } catch (Throwable $e) {
            // Nunca debe romper un despliegue: si algo falla aquí (permiso
            // roto, tabla ausente en un entorno atípico, etc.) el peor caso
            // es que algún usuario/rol se quede sin el backfill y haya que
            // correrlo a mano — no que el deploy entero aborte.
            Log::warning('[HelpdeskEmailLog] Fallo el backfill de helpdeskemaillog.view', [
                'error' => $e->getMessage(),
                'roles_updated' => $rolesUpdated,
                'users_updated' => $usersUpdated,
            ]);
        }

        try {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $e) {
            Log::warning('[HelpdeskEmailLog] No se pudo limpiar la caché de permisos tras el backfill', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Intencionalmente no reversible: esta migración solo AÑADE acceso de
     * lectura ('helpdeskemaillog.view') a quien ya podía ver la bandeja de
     * emails de tickets. Quitarlo automáticamente en down() podría revocar
     * acceso legítimo que un admin haya seguido concediendo por su cuenta
     * después de correr esta migración (no hay forma de distinguir, en
     * down(), "lo dio esta migración" de "lo dio un admin después") — y
     * revocar un permiso de acceso no es algo que deba deshacerse solo.
     * Si hace falta revertir, se hace a mano desde el panel de roles/usuarios.
     */
    public function down(): void {}
};
