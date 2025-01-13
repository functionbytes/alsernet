<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
abstract class Controller
{
    function generate_slack($table)
    {
        do {
            $slack = Str::random(6);
            $exist = DB::table($table)->where('slack', $slack)->exists();
        } while ($exist);

        return $slack;
    }

    function generate_number($table)
    {
        $lastId = DB::table($table)->max('id');
        return $lastId ? $lastId + 1 : 1;

    }

}
