<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'folder_id' => 'nullable|integer|exists:media_folders,id',
            'search' => 'nullable|string|max:255',
            'view' => 'nullable|string|in:all_media,trash,recent,favorites',
            'per_page' => 'nullable|integer|min:10|max:100',
            'page' => 'nullable|integer|min:1',
            'filter' => 'nullable|string',
            'sort_by' => 'nullable|string',
        ];
    }
}
