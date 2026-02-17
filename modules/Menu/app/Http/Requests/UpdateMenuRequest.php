<?php

namespace Modules\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $menuId = $this->route('menu')->id;

        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:menus,slug,'.$menuId,
            'location' => 'nullable|string',
            'status' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'menu name',
            'slug' => 'menu slug',
            'location' => 'menu location',
            'status' => 'menu status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The menu name is required.',
            'slug.unique' => 'This slug is already in use.',
        ];
    }
}
