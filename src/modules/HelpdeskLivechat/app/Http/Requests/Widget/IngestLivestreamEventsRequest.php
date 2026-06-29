<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class IngestLivestreamEventsRequest extends FormRequest
{
    private const MAX_PAYLOAD_BYTES = 256 * 1024;

    public function authorize(): bool
    {
        $token = (string) $this->header('X-Website-Token', '');

        if ($token === '') {
            return false;
        }

        $exists = Web::query()
            ->where('website_token', $token)
            ->where('enable_live_view', true)
            ->exists();

        if (! $exists) {
            return false;
        }

        $this->merge(['_validated_website_token' => $token]);

        return true;
    }

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:200'],
            'events.*' => ['array'],
            'batch_index' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $payload = json_encode($this->input('events', []));
            if ($payload !== false && strlen($payload) > self::MAX_PAYLOAD_BYTES) {
                $v->errors()->add('events', 'Payload too large (max 256 KB).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'events.required' => 'El batch de eventos no puede estar vacío.',
            'events.max' => 'El batch supera el máximo de 200 eventos.',
        ];
    }
}
