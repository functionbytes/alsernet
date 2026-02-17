<?php

namespace Modules\Menu\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
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
        return [
            'title' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:255',
            'target' => 'nullable|string|in:_self,_blank,_parent,_top',
            'icon' => 'nullable|string|max:255',
            'css_class' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:custom,page,post,category,route',
            'reference_id' => 'nullable|integer',
            'reference_type' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'item title',
            'url' => 'URL',
            'target' => 'link target',
            'icon' => 'icon class',
            'css_class' => 'CSS class',
            'type' => 'item type',
            'reference_id' => 'reference ID',
            'reference_type' => 'reference type',
        ];
    }
}
