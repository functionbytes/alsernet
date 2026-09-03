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
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update(['name' => str_replace('helpdeskemaillog.', 'helpdeskemailactivity.', $permission->name)]);
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
