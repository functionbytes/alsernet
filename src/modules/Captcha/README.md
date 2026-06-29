# Captcha

> reCAPTCHA and Math Captcha for form security

## Purpose

Integrates Google reCAPTCHA (v2 and v3) and a fallback math captcha to protect public forms against bots and spam. Exposes a `Captcha` facade, custom validation rules (`captcha`, `math_captcha`), and events to customize rendering.

## Main Components

- **Services**:
  - `Captcha` — reCAPTCHA v2 (checkbox) implementation
  - `CaptchaV3` — reCAPTCHA v3 (invisible, score-based) implementation
  - `MathCaptcha` — simple session-based math captcha

- **Facade**: `Captcha::reCaptchaEnabled()`, `Captcha::verify($token, $ip, $options)`

- **Events**:
  - `CaptchaRendering` — fired before rendering the captcha
  - `CaptchaRendered` — fired after rendering the captcha

- **Main Routes**:
  - `GET /panel/setting/captcha` — settings page
  - `PUT /panel/setting/captcha` — update settings

## Usage in Forms

```blade
{{-- In the Blade form --}}
{!! app('captcha')->display() !!}

{{-- In the Form Request --}}
'g-recaptcha-response' => ['required', 'captcha'],

{{-- For math captcha --}}
'math_answer' => ['required', 'math_captcha'],
```

## Configuration

File: `config/config.php`

Keys are managed from the admin panel and read via `setting()`:

| Key | Description |
|-----|-------------|
| `captcha_type` | Captcha type: `v2`, `v3` or `math` |
| `captcha_site_key` | Google reCAPTCHA site key |
| `captcha_secret` | Google reCAPTCHA secret key (**encrypted at rest**) |
| `recaptcha_score` | Minimum score for v3 (default: 0.6) |

## Security Notes

- The **Secret Key** is encrypted using Laravel's `encrypt()` helper before being stored in the database. It is decrypted automatically when the captcha service needs it.
- The settings form never renders the decrypted secret value; it only shows a masked placeholder.
- Both v2 and v3 verify the `hostname` returned by Google to prevent cross-domain token reuse.
- Tokens older than 5 minutes are rejected to mitigate replay attacks.

## Dependencies

- **Required**: `Modules\Theme\Services\NavService`
- **External**: Google reCAPTCHA account for v2/v3
