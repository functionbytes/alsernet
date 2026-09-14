<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class WebRtcOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $token = (string) $this->header('X-Website-Token', '');

        if ($token === '') {
            return false;
        }

        return Web::query()
            ->where('website_token', $token)
            ->where('enable_screen_share', true)
            ->exists();
    }

    public function rules(): array
    {
        return [
            'sdp' => ['required', 'string', 'min:32', 'max:65535'],
            'type' => ['required', 'string', 'in:offer'],
        ];
    }
}
