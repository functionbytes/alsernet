# Captcha

> reCAPTCHA and Math Captcha for form security

## Proposito

Integra Google reCAPTCHA (v2 y v3) y un captcha matematico de fallback para proteger formularios publicos contra bots y spam. Expone una Facade `Captcha`, reglas de validacion personalizadas (`captcha`, `math_captcha`) y eventos para personalizar el renderizado.

## Componentes principales

- **Servicios**:
  - `Captcha` — implementacion reCAPTCHA v2 (checkbox)
  - `CaptchaV3` — implementacion reCAPTCHA v3 (invisible, basado en score)
  - `MathCaptcha` — captcha matematico simple almacenado en sesion

- **Facade**: `Captcha::reCaptchaEnabled()`, `Captcha::verify($token, $ip, $options)`

- **Eventos**:
  - `CaptchaRendering` — disparado antes de renderizar el captcha
  - `CaptchaRendered` — disparado despues de renderizar el captcha

- **Rutas principales**:
  - `GET /panel/settings/captcha` — configuracion del tipo y claves

## Uso en formularios

```blade
{{-- En el formulario Blade --}}
{!! app('captcha')->display() !!}

{{-- En el Form Request --}}
'g-recaptcha-response' => ['required', 'captcha'],

{{-- Para math captcha --}}
'math_answer' => ['required', 'math_captcha'],
```

## Configuracion

Archivo: `config/config.php`

Las claves se gestionan desde el panel y se leen via `setting()`:

| Clave | Descripcion |
|-------|-------------|
| `captcha_type` | Tipo de captcha: `v2`, `v3` o `math` |
| `captcha_site_key` | Site key de Google reCAPTCHA |
| `captcha_secret` | Secret key de Google reCAPTCHA |
| `recaptcha_score` | Score minimo para v3 (default: 0.6) |

## Dependencias

- **Requeridos**: `Modules\Theme\Services\NavService`
- **Externos**: cuenta de Google reCAPTCHA para v2/v3
