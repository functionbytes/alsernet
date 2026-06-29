<?php

namespace Modules\Storage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class DiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('storage.manage');
    }

    protected function commonRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]+$/'],
            'driver' => ['required', 'string', 'in:local,ftp,sftp,s3'],
            'storage_type' => ['required_if:driver,local', 'nullable', 'string', 'in:public,private'],
            'host' => ['required_if:driver,ftp,sftp', 'nullable', 'string'],
            'username' => ['required_if:driver,ftp,sftp', 'nullable', 'string'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'bucket' => ['required_if:driver,s3', 'nullable', 'string'],
            'key' => ['required_if:driver,s3', 'nullable', 'string'],
            'region' => ['required_if:driver,s3', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del disco es obligatorio.',
            'name.regex' => 'El nombre solo puede contener letras, números y guiones bajos.',
            'driver.required' => 'El tipo de almacenamiento es obligatorio.',
            'driver.in' => 'El tipo de almacenamiento no es válido.',
            'storage_type.required_if' => 'La ubicación del almacenamiento es obligatoria para discos locales.',
            'host.required_if' => 'El host es obligatorio para conexiones FTP/SFTP.',
            'username.required_if' => 'El usuario es obligatorio para conexiones FTP/SFTP.',
            'bucket.required_if' => 'El bucket es obligatorio para Amazon S3.',
            'key.required_if' => 'El Access Key ID es obligatorio para Amazon S3.',
            'region.required_if' => 'La región es obligatoria para Amazon S3.',
            'port.integer' => 'El puerto debe ser un número entero.',
            'port.min' => 'El puerto debe ser mayor a 0.',
            'port.max' => 'El puerto no puede ser mayor a 65535.',
            'name.max' => 'El nombre del disco no puede exceder 50 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre del disco',
            'driver' => 'tipo de almacenamiento',
            'storage_type' => 'ubicación',
            'host' => 'host',
            'username' => 'usuario',
            'password' => 'contraseña',
            'port' => 'puerto',
            'bucket' => 'bucket',
            'key' => 'Access Key ID',
            'secret' => 'Secret Access Key',
            'region' => 'región',
        ];
    }
}
