<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVerificationCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.metas.index') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'seo_google_verification' => ['nullable', 'string', 'max:255'],
            'seo_bing_verification' => ['nullable', 'string', 'max:255'],
            'seo_pinterest_verification' => ['nullable', 'string', 'max:255'],
            'seo_baidu_verification' => ['nullable', 'string', 'max:255'],
            'seo_yandex_verification' => ['nullable', 'string', 'max:255'],
        ];
    }
}
