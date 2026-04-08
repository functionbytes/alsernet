<?php

namespace Modules\Captcha\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Captcha\Facades\Captcha;
use Modules\Captcha\Http\Requests\Settings\CaptchaSettingRequest;
use Modules\Core\Models\Setting;

class CaptchaSettingController extends Controller
{
    public function edit()
    {
        return view('captcha::settings.edit', [
            'enable_captcha' => old('enable_captcha', Captcha::reCaptchaEnabled()),
            'captcha_type' => old('captcha_type', setting('captcha_type', 'v2')),
            'captcha_site_key' => old('captcha_site_key', setting('captcha_site_key')),
            'captcha_secret' => old('captcha_secret', setting('captcha_secret')),
            'captcha_score' => old('captcha_score', setting('captcha_score', 0.5)),
            'enable_math_captcha' => old('enable_math_captcha', setting('enable_math_captcha', false)),
            'enable_math_captcha_contact_form' => old('enable_math_captcha_contact_form', setting('enable_math_captcha_for_contact_form', false)),
            'enable_math_captcha_newsletter_form' => old('enable_math_captcha_newsletter_form', setting('enable_math_captcha_for_newsletter_form', false)),
        ]);
    }

    public function update(CaptchaSettingRequest $request): RedirectResponse
    {
        foreach ($request->validated() as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Configuracion de captcha guardada correctamente.');
    }
}
