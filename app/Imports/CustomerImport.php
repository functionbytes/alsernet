<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\CustomerSetting;
use App\Models\Customer;
use Throwable;
use Exception;
use Hash;

class CustomerImport implements  ToModel, WithHeadingRow,SkipsOnError, WithValidation
{
    use Importable, SkipsErrors;
    public function model(array $row)
    {
        if(isset($row['role']) || isset($row['empid'])){
            throw new Exception('importingerror');
        }else{
            $customer = Customer::where('email', $row['email'])->first();

            if ($customer) {
                $customer->update([
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'username' => $row['firstname'] .' '. $row['lastname'],
                    'password' => Hash::make($row['password']),
                ]);
            } else {
                $customer = Customer::create([
                    'firstname' => $row['firstname'],
                    'lastname' => $row['lastname'],
                    'username' => $row['firstname'] .' '. $row['lastname'],
                    'email' => $row['email'],
                    'password' => Hash::make($row['password']),
                    'userType' => 'Customer',
                    'timezone' => setting('default_timezone'),
                    'status' => '1',
                    'verified' => '1',
                    'image' => null,
                ]);
            }

            $customerSetting = CustomerSetting::where('custs_id', $customer->id)->first();
            if (!$customerSetting) {
                $customerSetting = new CustomerSetting();
                $customerSetting->custs_id = $customer->id;
                $customerSetting->darkmode = setting('DARK_MODE');
                $customerSetting->save();
            }

            return $customer;
        }
    }

    public function rules(): array
    {
        return  [
            '*.firstname' => ['required','string',],
            '*.lastname' => ['required','string',],
            '*.email' => ['required','string'],
            '*.password' => ['required'],
        ];


    }
}
