<?php

namespace Modules\Social\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Social\Enums\SocialNetwork;
use Modules\Social\Models\SocialAccount;

class StoreSocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', SocialAccount::class);
    }

    public function rules(): array
    {
        $networkValues = implode(',', array_column(SocialNetwork::cases(), 'value'));

        return [
            'account_id' => 'required|exists:chat_accounts,id',
            'network' => "required|string|in:{$networkValues}",
            'username' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'access_token' => 'nullable|string',
            'status' => 'required|integer|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'network.required' => 'Debes seleccionar una red social',
            'network.in' => 'La red social seleccionada no es válida',
            'username.required' => 'El nombre de usuario es obligatorio',
            'username.max' => 'El nombre de usuario no puede exceder 255 caracteres',
            'name.required' => 'El nombre para mostrar es obligatorio',
            'name.max' => 'El nombre para mostrar no puede exceder 255 caracteres',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'account_id' => auth()->user()->account_id,
            'status' => 1, // Active by default
        ]);
    }
}
