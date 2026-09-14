<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskLivechat\Models\Channels\Web;

class WebRtcIceRequest extends FormRequest
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
            'candidate' => ['required', 'array'],
            'candidate.candidate' => ['nullable', 'string', 'max:5000'],
            'candidate.sdpMid' => ['nullable', 'string', 'max:50'],
            'candidate.sdpMLineIndex' => ['nullable', 'integer'],
        ];
    }
}
