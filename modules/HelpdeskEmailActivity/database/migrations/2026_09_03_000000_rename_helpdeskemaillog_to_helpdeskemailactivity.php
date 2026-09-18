<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renombra el modulo HelpdeskEmailLog -> HelpdeskEmailActivity de forma
 * estructural (no solo el texto visible, ver commit anterior de solo
 * naming). Los permisos y settings viven en tablas por NOMBRE/CLAVE
 * (string), asi que hay que reescribir esos strings -- un simple UPDATE del
 * campo `name`/`key` preserva TODOS los vinculos existentes por id
 * (role_has_permissions/model_has_permissions referencian por
 * permission_id, no por nombre), asi que ningun usuario pierde acceso.
 *
 * Verificado antes de escribir esto: 4 filas en `permissions` con
 * `helpdeskemaillog.*`, 213 vinculos en role_has_permissions+model_has_permissions
 * apuntando a esos 4 ids, y 7 filas en `settings` con clave `helpdeskemaillog.*`.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('name', 'like', 'helpdeskemaillog.%')
            ->get(['id', 'name'])
            ->each(function ($permission) {
                $newName = str_replace('helpdeskemaillog.', 'helpdeskemailactivity.', $permission->name);

                // En algunos entornos ya existe una fila con el nombre nuevo
                // (p.ej. un seeder del modulo nuevo corrio antes que esta
                // migracion y la creo desde cero, vacia). Un UPDATE directo
                // chocaria con el UNIQUE de `name`. En ese caso, en vez de
                // renombrar, se trasladan los vinculos reales (role_has_
                // permissions/model_has_permissions) de la fila vieja a la
                // que ya existe y se borra la vieja — mismo resultado final,
                // sin perder los permisos que alguien ya tenia concedidos.
                $existing = DB::table('permissions')->where('name', $newName)->first(['id']);

                if ($existing && $existing->id !== $permission->id) {
                    // role_has_permissions: clave (role_id, permission_id).
                    foreach (DB::table('role_has_permissions')->where('permission_id', $permission->id)->get(['role_id']) as $row) {
                        $alreadyLinked = DB::table('role_has_permissions')
                            ->where('role_id', $row->role_id)
                            ->where('permission_id', $existing->id)
                            ->exists();

                        if ($alreadyLinked) {
                            DB::table('role_has_permissions')
                                ->where('role_id', $row->role_id)
                                ->where('permission_id', $permission->id)
                                ->delete();
                        } else {
                            DB::table('role_has_permissions')
                                ->where('role_id', $row->role_id)
                                ->where('permission_id', $permission->id)
                                ->update(['permission_id' => $existing->id]);
                        }
                    }

                    // model_has_permissions: clave (model_id, model_type, permission_id).
                    foreach (DB::table('model_has_permissions')->where('permission_id', $permission->id)->get(['model_id', 'model_type']) as $row) {
                        $alreadyLinked = DB::table('model_has_permissions')
                            ->where('model_id', $row->model_id)
                            ->where('model_type', $row->model_type)
                            ->where('permission_id', $existing->id)
                            ->exists();

                        if ($alreadyLinked) {
                            DB::table('model_has_permissions')
                                ->where('model_id', $row->model_id)
                                ->where('model_type', $row->model_type)
                                ->where('permission_id', $permission->id)
                                ->delete();
                        } else {
                            DB::table('model_has_permissions')
                                ->where('model_id', $row->model_id)
                                ->where('model_type', $row->model_type)
                                ->where('permission_id', $permission->id)
                                ->update(['permission_id' => $existing->id]);
                        }
                    }

                    DB::table('permissions')->where('id', $permission->id)->delete();

                    return;
                }

                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update(['name' => $newName]);
            });

        DB::table('settings')
            ->where('key', 'like', 'helpdeskemaillog.%')
            ->get(['id', 'key'])
            ->each(function ($setting) {
                DB::table('settings')
                    ->where('id', $setting->id)
                    ->update(['key' => str_replace('helpdeskemaillog.', 'helpdeskemailactivity.', $setting->key)]);
            });
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'like', 'helpdeskemailactivity.%')
            ->get(['id', 'name'])
            ->each(function ($permission) {
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update(['name' => str_replace('helpdeskemailactivity.', 'helpdeskemaillog.', $permission->name)]);
            });

        DB::table('settings')
            ->where('key', 'like', 'helpdeskemailactivity.%')
            ->get(['id', 'key'])
            ->each(function ($setting) {
                DB::table('settings')
                    ->where('id', $setting->id)
                    ->update(['key' => str_replace('helpdeskemailactivity.', 'helpdeskemaillog.', $setting->key)]);
            });
    }
};
