<?php

namespace Modules\Remarketing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.stores.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'in:pending,active,error,paused'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'domain' => 'dominio',
            'status' => 'estado',
        ];
    }
}
