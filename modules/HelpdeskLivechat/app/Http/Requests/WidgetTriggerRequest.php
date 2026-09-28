<?php

namespace Modules\HelpdeskLivechat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\HelpdeskLivechat\Models\WidgetTrigger;

class WidgetTriggerRequest extends FormRequest
{
    /** Tipos numéricos (umbral mínimo/máximo). */
    private const NUMERIC = ['site_time', 'page_time', 'pages_visited', 'products_viewed', 'cart_value', 'cart_items'];

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdesk.livechat.triggers.manage');
    }

    public function rules(): array
    {
        return [
            'web_id' => ['required', 'integer', Rule::exists('helpdesk.helpdesk_channel_webs', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'match' => ['required', Rule::in(['all', 'any'])],
            'action' => ['required', Rule::in(WidgetTrigger::ACTIONS)],
            'message' => ['nullable', 'string', 'max:500', 'required_if:action,message'],
            'frequency' => ['required', Rule::in(WidgetTrigger::FREQUENCIES)],
            'messages' => ['nullable', 'array'],
            'messages.*' => ['nullable', 'string', 'max:500'],
            'conditions' => ['required', 'array', 'min:1', 'max:10'],
            'conditions.*.type' => ['required', Rule::in(WidgetTrigger::CONDITION_TYPES)],
            'conditions.*.op' => ['required', 'string', 'max:10'],
            'conditions.*.value' => ['required', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            foreach ((array) $this->input('conditions', []) as $i => $c) {
                $type = $c['type'] ?? null;
                $op = $c['op'] ?? null;
                $value = trim((string) ($c['value'] ?? ''));
                if (! $this->validCondition($type, $op, $value)) {
                    $v->errors()->add("conditions.$i.value", __('helpdesklivechat::triggers.invalid_condition', ['n' => $i + 1]));
                }
            }
        });
    }

    private function validCondition(?string $type, ?string $op, string $value): bool
    {
        return match (true) {
            in_array($type, self::NUMERIC, true) => in_array($op, ['gte', 'lte'], true) && is_numeric($value) && (float) $value >= 0 && (float) $value <= 1000000,
            in_array($type, ['product_viewed', 'current_product'], true) => $op === 'eq' && ctype_digit($value),
            $type === 'url' => in_array($op, ['contains', 'starts', 'ends', 'equals'], true) && $value !== '',
            $type === 'locale' => $op === 'eq' && (bool) preg_match('/^[a-z]{2}$/', $value),
            $type === 'weekday' => $op === 'in' && (bool) preg_match('/^[1-7](,[1-7])*$/', $value),
            $type === 'hour_range' => $op === 'between' && (bool) preg_match('/^(\d{1,2})-(\d{1,2})$/', $value, $m) && (int) $m[1] <= 23 && (int) $m[2] >= 1 && (int) $m[2] <= 24 && (int) $m[1] < (int) $m[2],
            default => false,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function triggerData(): array
    {
        $data = $this->validated();

        return [
            'web_id' => (int) $data['web_id'],
            'name' => $data['name'],
            'priority' => (int) ($data['priority'] ?? 50),
            'match' => $data['match'],
            'action' => $data['action'],
            'message' => $data['action'] === 'message' ? trim((string) $data['message']) : null,
            'messages' => $data['action'] === 'message'
                ? array_filter(array_map(
                    fn ($m) => trim((string) $m),
                    array_intersect_key((array) ($data['messages'] ?? []), array_flip(WidgetTrigger::MESSAGE_LANGUAGES))
                ), fn ($m) => $m !== '')
                : null,
            'frequency' => $data['frequency'],
            'is_active' => $this->boolean('is_active'),
            'conditions' => array_values(array_map(fn (array $c) => [
                'type' => $c['type'],
                'op' => $c['op'],
                'value' => trim((string) $c['value']),
            ], $data['conditions'])),
        ];
    }
}
