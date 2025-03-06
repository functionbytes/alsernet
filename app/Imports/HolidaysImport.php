<?php

namespace App\Imports;


use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Mail\mailmailablesend;
use Illuminate\Validation\Rule;
use App\Models\CustomerSetting;
use App\Models\Holiday;
use App\Models\Customer;
use Carbon\Carbon;
use Throwable;
use Hash;
use Mail;

class HolidaysImport implements ToModel, WithHeadingRow,SkipsOnError, WithValidation
{
    use Importable, SkipsErrors;
    public function model(array $row)
    {
        if($row['primaray_color']){
            $primary_color = $row['primaray_color'];
        }else{
            $primary_color = "rgba(0, 0, 0, 1)";
        }

        if($row['secondary_color']){
            $secondary_color = $row['secondary_color'];
        }else{
            $secondary_color = "rgba(0, 0, 0, 1)";
        }

        $holiday =  Holiday::create([
            'occasion' => $row['occasion'],
            'startdate' => Carbon::parse($row['startdate']),
            'enddate' => Carbon::parse($row['enddate']),
            'holidaydescription' => $row['holidaydescription'],
            'primaray_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'status' => '1',
        ]);

        return $holiday;
    }

    public function rules(): array
    {
        return  [
            '*.occasion' => ['required','string',],
            '*.startdate' => ['required','date', 'after_or_equal:' . now()->format('Y-m-d')],
            '*.enddate' => ['required','date', 'after_or_equal:' . now()->format('Y-m-d')],
            '*.holidaydescription' => ['required'],
        ];
    }

}
