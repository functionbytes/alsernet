<?php

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('notification.settings.update');
    }

    public function rules(): array
    {
        return [
            'cleanup_enabled' => ['required', 'boolean'],
            'cleanup_days' => ['required', 'integer', 'min:1', 'max:365'],
            'push_enabled' => ['required', 'boolean'],
            'push_max_retries' => ['required', 'integer', 'min:1', 'max:10'],
            'channel_database' => ['required', 'boolean'],
            'channel_mail' => ['required', 'boolean'],
            'channel_push' => ['required', 'boolean'],
            'retention_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'cleanup_days.min' => 'Los días de limpieza deben ser al menos 1.',
            'cleanup_days.max' => 'Los días de limpieza no pueden exceder 365.',
            'push_max_retries.min' => 'Los reintentos deben ser al menos 1.',
            'push_max_retries.max' => 'Los reintentos no pueden exceder 10.',
            'retention_days.min' => 'Los días de retención deben ser al menos 1.',
            'retention_days.max' => 'Los días de retención no pueden exceder 365.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cleanup_enabled' => 'limpieza automática',
            'cleanup_days' => 'días de limpieza',
            'push_enabled' => 'notificaciones push',
            'push_max_retries' => 'máximo de reintentos',
            'channel_database' => 'canal base de datos',
            'channel_mail' => 'canal correo',
            'channel_push' => 'canal push',
            'retention_days' => 'días de retención',
        ];
    }
}
