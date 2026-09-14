<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopyMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.create');
    }

    public function rules(): array
    {
        return [];
    }
}
