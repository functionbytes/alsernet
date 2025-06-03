<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient' => [
                'nullable',
                'email',
                'max:255'
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500'
            ]
        ];
    }
}

