<?php

namespace Modules\Storage\Http\Requests;

class UpdateDiskRequest extends DiskRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('storage.update');
    }

    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'key' => ['nullable', 'string'],    // blank = keep existing
            'password' => ['nullable', 'string'],
            'secret' => ['nullable', 'string'], // blank = keep existing
        ]);
    }
}
