<?php

declare(strict_types=1);

namespace Modules\Captcha\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Captcha\Facades\Captcha;
use Modules\Captcha\Http\Requests\Settings\CaptchaSettingRequest;
use Modules\Core\Models\Setting;

class CaptchaSettingController extends Controller
{
    public function edit(): View
    {
        return view('captcha::settings.edit', [
            'enable_captcha' => old('enable_captcha', Captcha::reCaptchaEnabled()),
            'captcha_type' => old('captcha_type', setting('captcha_type', 'v2')),
            'captcha_site_key' => old('captcha_site_key', setting('captcha_site_key')),
            // Never send the secret key value to the view — render a masked placeholder instead.
            // The field stays blank; submitting blank preserves the stored value.
            'captcha_secret_is_set' => ! empty($this->resolveSecret()),
            'captcha_score' => old('captcha_score', setting('captcha_score', 0.5)),
            'enable_math_captcha' => old('enable_math_captcha', setting('enable_math_captcha', false)),
            'enable_math_captcha_contact_form' => old('enable_math_captcha_contact_form', setting('enable_math_captcha_contact_form', false)),
            'enable_math_captcha_newsletter_form' => old('enable_math_captcha_newsletter_form', setting('enable_math_captcha_newsletter_form', false)),
        ]);
    }

    public function update(CaptchaSettingRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated() as $key => $value) {
                if ($key === 'captcha_secret' && empty($value)) {
                    continue;
                }

                if ($key === 'captcha_secret' && is_string($value)) {
                    $value = 'encrypted:'.encrypt($value);
                }

                Setting::set($key, $value);
            }
        });

        return redirect()->back()->with('success', trans('captcha::captcha.settings.saved_successfully'));
    }

    private function resolveSecret(): ?string
    {
        $secret = setting('captcha_secret');

        if (! is_string($secret)) {
            return null;
        }

        if (str_starts_with($secret, 'encrypted:')) {
            try {
                return decrypt(substr($secret, 10));
            } catch (DecryptException $e) {
                logger()->error('Captcha secret decryption failed', ['error' => $e->getMessage()]);

                return null;
            }
        }

        return $secret;
    }
}
