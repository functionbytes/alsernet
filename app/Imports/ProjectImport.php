<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use App\Models\Projects;
use Throwable;

class ProjectImport implements ToModel, WithHeadingRow, SkipsOnError, WithValidation
{

    use Importable, SkipsErrors;

    public function model(array $row)
    {
        $project =  new Projects([
            'name' => $row['projecttitle'],
        ]);

        return $project;
    }

    public function onError(Throwable $error)
    {
    }

    public function rules(): array
    {
        return  [
            '*.projecttitle' => ['required','string', 'unique:projects,name'],
        ];

    }


}
