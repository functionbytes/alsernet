<?php

namespace Modules\Supplier\Http\Requests\Sync;

use Illuminate\Foundation\Http\FormRequest;

class SaveSyncSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.sync.config') ?? false;
    }

    public function rules(): array
    {
        return [
            'default_batch_size' => ['required', 'integer', 'min:1', 'max:1000'],
            'erp_timeout'        => ['required', 'integer', 'min:5', 'max:300'],
            'max_retries'        => ['required', 'integer', 'min:0', 'max:10'],
            'erp_base_url'       => ['required', 'url', 'max:500'],
            'sync_enabled'       => ['required', 'boolean'],
            'log_level'          => ['required', 'in:debug,info,warning,error'],
            'filter_date_from'          => ['nullable', 'date_format:Y-m-d'],
            'default_limit'             => ['nullable', 'integer', 'min:0'],
            'default_mode'              => ['nullable', 'in:filter,legacy'],
            'default_force'             => ['nullable', 'in:0,1'],
            'default_description_empty' => ['nullable', 'in:0,1'],
            'default_web_filter'        => ['nullable', 'in:,1,2'],
            'default_skip_ai'           => ['nullable', 'in:0,1'],
            'default_dry_run'           => ['nullable', 'in:0,1'],
            'default_register_only'     => ['nullable', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'default_batch_size.required' => 'El tamano de batch es obligatorio.',
            'default_batch_size.integer' => 'El tamano de batch debe ser numerico.',
            'default_batch_size.min' => 'El tamano de batch debe ser al menos 1.',
            'default_batch_size.max' => 'El tamano de batch no puede superar 1000.',
            'erp_timeout.min' => 'El timeout debe ser al menos 5 segundos.',
            'erp_timeout.max' => 'El timeout no puede superar 300 segundos.',
            'erp_base_url.url' => 'La URL del ERP no es valida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'default_batch_size' => 'tamano de batch',
            'erp_timeout' => 'timeout ERP',
            'max_retries' => 'reintentos maximos',
            'erp_base_url' => 'URL del ERP',
            'sync_enabled' => 'sincronizacion activada',
            'log_level' => 'nivel de log',
        ];
    }
}
