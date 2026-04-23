<?php

declare(strict_types=1);

namespace Modules\Captcha\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Captcha\Facades\Captcha;
use Modules\Core\Rules\OnOffRule;

class CaptchaSettingRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'enable_captcha' => $onOffRule = new OnOffRule,
            'captcha_type' => ['nullable', 'in:v2,v3', $enableCaptchaRule = 'required_if:enable_captcha,1'],
            'captcha_hide_badge' => $onOffRule,
            'captcha_show_disclaimer' => $onOffRule,
            'captcha_site_key' => [
                'nullable',
                'string',
                $enableCaptchaRule,
                'regex:/^6[LM][a-zA-Z0-9_-]{38,40}$/',
            ],
            'captcha_secret' => [
                'nullable',
                'string',
                $enableCaptchaRule,
                'min:30',
                'regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'enable_math_captcha' => $onOffRule,
            'recaptcha_score' => ['nullable', Rule::in(Captcha::scores()), 'required_if:captcha_type,v3'],
        ];

        $rules = [
            ...$rules,
            ...$this->formSelectorRules('enable_math_captcha'),
            ...$this->formSelectorRules('enable_recaptcha'),
        ];

        return $rules;
    }

    protected function formSelectorRules(string $key): array
    {
        $rules = [];

        foreach (array_keys(Captcha::getFormsSupport()) as $form) {
            $rules[Captcha::formSettingKey($form, $key)] = new OnOffRule;
        }

        return $rules;
    }

    public function authorize(): bool
    {
        return $this->user()?->can('captcha.settings.update')
            || $this->user()?->hasRole(['admin', 'super-admin'])
            || false;
    }

    public function messages(): array
    {
        return [
            'captcha_site_key.regex' => trans('captcha::captcha.settings.site_key_invalid_format'),
            'captcha_secret.regex' => trans('captcha::captcha.settings.secret_key_invalid_chars'),
            'captcha_secret.min' => trans('captcha::captcha.settings.secret_key_too_short'),
        ];
    }
}
