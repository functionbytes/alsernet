<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\HelpdeskPrestashop\Services\Ext\OpslogBridgeLogService;

/**
 * Filtros del registro del puente: ventana (1 h, 24 h, 7 días) y resultado.
 */
class OpslogBridgeLogRequest extends FormRequest
{
    public const WINDOWS = [1, 24, 168];

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdeskprestashop.ops.view');
    }

    public function rules(): array
    {
        return [
            'hours' => ['nullable', 'integer', Rule::in(self::WINDOWS)],
            'result' => ['nullable', 'string', Rule::in(OpslogBridgeLogService::RESULTS)],
        ];
    }

    public function hours(): int
    {
        return (int) ($this->validated('hours') ?? 24);
    }

    public function result(): string
    {
        return (string) ($this->validated('result') ?? 'all');
    }
}
