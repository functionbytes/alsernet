<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
abstract class Controller
{
    function generate_uid($table)
    {
        do {
            $uid = Str::uuid();
            $exist = DB::table($table)->where('uid', $uid)->exists();
        } while ($exist);

        return $uid;
    }


    function generate_prstashop_uid($table)
    {
        do {
            $slack = Str::random(6);
            $exist = DB::connection('prestashop')->table($table)->where('slack', $slack)->exists();
        } while ($exist);

        return $slack;
    }

    function generate_uuid($table)
    {
        do {
            $slack = Str::uuid();  // Generar un UUID único
            $exist = DB::table($table)->where('slack', $slack)->exists();  // Verificar si el UUID ya existe
        } while ($exist);  // Si existe, generar otro UUID

        return $slack;  // Retornar el UUID único
    }



    function generate_number($table)
    {
        $lastId = DB::table($table)->max('id');
        return $lastId ? $lastId + 1 : 1;

    }

}
