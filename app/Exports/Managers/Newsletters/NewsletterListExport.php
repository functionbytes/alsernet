<?php

namespace App\Exports\Managers\Newsletters;

use App\Models\Course\Course;
use App\Models\Enterprise\Enterprise;
use App\Models\Location;
use App\Models\Order\OrderCondition;
use App\Models\Order\OrderMethod;
use App\Models\Order\OrderType;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class NewsletterListExport implements FromQuery, Responsable, WithMapping, WithHeadings, WithStrictNullComparison
{
    use Exportable;

    private $list;
    private $start;
    private $end;

    public function __construct($list,$start,$end){

        $this->list = $list;
        $this->start = $start;
        $this->end = $end;
    }


    public function query() {

        $query = DB::table('newsletters')
            ->join('subscribers_lists_users', function ($join) {
                $join->on('subscribers_lists_users.subscribers_id', '=', 'newsletters.id');
            });

        if ($this->list != 0) {
            $query->where('subscribers_lists_users.list_id', '=', $this->list);
        }

        //if ($this->start !== $this->end) {
        //    $query->whereBetween('subscribers_lists_users.created_at', [$this->start, $this->end]);
        //}

        return $query->select(
            'newsletters.firstname',
            'newsletters.lastname',
            'newsletters.email',
            'newsletters.updated_at',
        )->orderBy('subscribers_lists_users.updated_at', 'desc');


    }


    public function map($row): array
    {

        return [
            $row->firstname,
            $row->lastname,
            strtolower($row->email),
            date('Y-m-d', strtotime($row->updated_at)),
        ];

    }

    public function headings(): array
    {
        return [
            'NOMBRES',
            'APELLIDOS',
            'CORREO ELECTRONICO',
            'FECHA DE SUSCRIPCION',
        ];
    }

}

