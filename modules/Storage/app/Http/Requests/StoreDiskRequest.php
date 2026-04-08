<?php

namespace Modules\Storage\Http\Requests;

class StoreDiskRequest extends DiskRequest
{
    public function rules(): array
    {
        return array_merge($this->commonRules(), [
            'password' => ['nullable', 'string'],
            'secret' => ['required_if:driver,s3', 'nullable', 'string'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'secret.required_if' => 'El Secret Access Key es obligatorio para Amazon S3.',
        ]);
    }
}
