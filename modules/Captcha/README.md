# Captcha Module

## Overview

The Captcha module provides comprehensive form security through Google reCAPTCHA (v2 and v3) and Math Captcha implementations for the Inoqualab platform.

## Features

- **reCAPTCHA v2**: Classic checkbox captcha verification
- **reCAPTCHA v3**: Invisible captcha with score-based verification
- **Math Captcha**: Simple math-based verification without external dependencies
- **Flexible Integration**: Easy integration with any form in the application
- **Multiple Form Support**: Register and configure captcha for different forms independently
- **Customizable Settings**: Configure site keys, secret keys, and verification thresholds

## Installation

The module is automatically registered when placed in the `modules/` directory.

### Configuration

1. **Set up reCAPTCHA credentials** in your settings:
   - `captcha_site_key`: Your Google reCAPTCHA site key
   - `captcha_secret`: Your Google reCAPTCHA secret key
   - `captcha_type`: Either 'v2' or 'v3'
   - `enable_captcha`: Enable/disable reCAPTCHA
   - `enable_math_captcha`: Enable/disable Math Captcha

2. **Math Captcha configuration** (in `config/general.php`):
   ```php
   'math-captcha' => [
       'operands' => ['+', '-', '*'],
       'rand-min' => 2,
       'rand-max' => 5,
   ]
   ```

## Usage

### Using the Facade

```php
use Modules\Captcha\Facades\Captcha;

// Display reCAPTCHA
{!! Captcha::display() !!}

// Verify reCAPTCHA
$isValid = Captcha::verify($response, $clientIp);

// Check if enabled
if (Captcha::isEnabled()) {
    // Captcha is enabled
}
```

### Using Math Captcha

```php
use Modules\Captcha\MathCaptcha;

$mathCaptcha = app('math-captcha');

// Display label
echo $mathCaptcha->label();

// Display input
echo $mathCaptcha->input();

// Verify answer
$isValid = $mathCaptcha->verify($userInput);

// Reset (generate new question)
$mathCaptcha->reset();
```

### Form Integration

Register a form to support captcha:

```php
use Modules\Captcha\Facades\Captcha;

Captcha::registerFormSupport(
    LoginForm::class,
    LoginRequest::class,
    'Login Form'
);
```

### Validation Rules

Add to your request validation:

```php
public function rules(): array
{
    return [
        // Other rules...
        ...Captcha::rules(), // For reCAPTCHA
        // Or
        ...Captcha::mathCaptchaRules(), // For Math Captcha
    ];
}
```

### Blade Templates

The module provides the following view templates:

- `captcha::header-meta` - Meta tags for preconnecting to Google
- `captcha::v2.html` - reCAPTCHA v2 HTML element
- `captcha::v2.script` - reCAPTCHA v2 JavaScript
- `captcha::v3.html` - reCAPTCHA v3 hidden input
- `captcha::v3.head` - reCAPTCHA v3 head styles
- `captcha::v3.script` - reCAPTCHA v3 JavaScript

## API Reference

### Captcha Contract

**Methods:**

- `verify(string $response, string $clientIp, array $options = []): bool` - Verify captcha response
- `display(array $attributes = [], array $options = []): ?string` - Display captcha HTML
- `rules(): array` - Get validation rules for reCAPTCHA
- `isEnabled(): bool` - Check if reCAPTCHA is enabled
- `reCaptchaEnabled(): bool` - Check if reCAPTCHA is enabled
- `mathCaptchaEnabled(): bool` - Check if Math Captcha is enabled
- `mathCaptchaRules(): array` - Get validation rules for Math Captcha
- `captchaType(): string` - Get captcha type (v2/v3)
- `scores(): array` - Get available score thresholds (for v3)
- `registerFormSupport(string $form, string $request, string $title): static` - Register form support
- `getFormsSupport(): array` - Get all registered forms
- `formByRequest(string $request): ?string` - Get form by request class
- `formSettingKey(string $form, string $key): string` - Get setting key for form
- `formSetting(string $form, string $key, mixed $default = false): mixed` - Get form-specific setting

### MathCaptcha

**Methods:**

- `label(): string` - Get the math question label
- `getMathLabelOnly(): string` - Get only the math expression
- `input(array $attributes = []): string` - Generate input HTML
- `verify(string $value): bool` - Verify the answer
- `reset(): void` - Reset and generate new question

## Events

The module dispatches the following events:

- `Modules\Captcha\Events\CaptchaRendering` - Before captcha is rendered
- `Modules\Captcha\Events\CaptchaRendered` - After captcha is rendered

## Security

### reCAPTCHA v2
- Uses checkbox verification
- Requires user interaction
- Highly effective against bots

### reCAPTCHA v3
- Invisible verification
- Score-based (0.0 to 1.0)
- Configurable threshold
- Better user experience

### Math Captcha
- No external dependencies
- Session-based verification
- Simple and effective for basic protection
- Configurable difficulty via min/max ranges

## Configuration Files

### `config/general.php`
Math captcha configuration including operands and random number ranges.

### `config/permissions.php`
Permission definitions for captcha settings.

## Dependencies

- PHP ^8.2
- Laravel Framework
- Guzzle HTTP Client (for reCAPTCHA API calls)

## License

This module is proprietary software developed for the Inoqualab platform.

## Support

For issues, questions, or contributions, please contact the development team.
