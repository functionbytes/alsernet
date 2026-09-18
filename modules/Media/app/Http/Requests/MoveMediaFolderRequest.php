<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveMediaFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.update');
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'La carpeta de destino no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => 'carpeta destino',
        ];
    }
}
