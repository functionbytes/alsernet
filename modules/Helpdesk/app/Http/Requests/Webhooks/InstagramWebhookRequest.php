<?php

namespace Modules\Helpdesk\Http\Requests\Webhooks;

use Illuminate\Foundation\Http\FormRequest;

class InstagramWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->signatureIsValid();
    }

    public function rules(): array
    {
        return [];
    }

    private function signatureIsValid(): bool
    {
        // Instagram shares the Facebook app secret (same Meta platform)
        $appSecret = config('helpdesk.integrations.facebook.app_secret', '');

        if (! filled($appSecret)) {
            return true;
        }

        $signature = $this->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $provided = substr($signature, 7);
        $expected = hash_hmac('sha256', $this->getContent(), $appSecret);

        return hash_equals($expected, $provided);
    }
}
