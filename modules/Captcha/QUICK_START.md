# Captcha Module - Quick Start Guide

## 1. Installation

The module is already installed in `modules/Captcha/`. Just ensure it's activated:

```bash
# Clear caches
php artisan config:clear
php artisan cache:clear

# Regenerate autoload
composer dump-autoload
```

## 2. Get Google reCAPTCHA Keys

1. Visit [Google reCAPTCHA Admin](https://www.google.com/recaptcha/admin)
2. Register a new site
3. Choose reCAPTCHA v2 or v3 (or both)
4. Add your domain(s)
5. Copy the **Site Key** and **Secret Key**

## 3. Configure Settings

### Via Admin Panel
1. Go to **Settings > Others > Captcha**
2. Enable reCAPTCHA or Math Captcha
3. Select captcha type (v2 or v3)
4. Enter your Site Key and Secret Key
5. Configure additional options
6. Save settings

### Via Database (Alternative)
```sql
INSERT INTO settings (key, value) VALUES
('enable_captcha', '1'),
('captcha_site_key', 'your-site-key-here'),
('captcha_secret', 'your-secret-key-here'),
('captcha_type', 'v2'),
('enable_math_captcha', '0');
```

## 4. Basic Usage

### Display reCAPTCHA in Your Form

```blade
<!-- In your Blade template -->
<form method="POST" action="/submit">
    @csrf
    
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    
    <!-- Add reCAPTCHA -->
    {!! app('captcha')->display() !!}
    
    <button type="submit">Submit</button>
</form>
```

### Or using the Facade

```blade
@use(Modules\Captcha\Facades\Captcha)

{!! Captcha::display() !!}
```

### Validate in Your Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Captcha\Facades\Captcha;

class ContactRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
            ...Captcha::rules(), // Add captcha validation
        ];
    }
}
```

## 5. Math Captcha Usage

### Display Math Captcha

```blade
@php
    $mathCaptcha = app('math-captcha');
@endphp

<form method="POST" action="/submit">
    @csrf
    
    <div class="form-group">
        <label>{{ $mathCaptcha->label() }}</label>
        {!! $mathCaptcha->input(['class' => 'form-control']) !!}
        @error('math-captcha')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    
    <button type="submit">Submit</button>
</form>
```

### Validate Math Captcha

```php
public function rules(): array
{
    return [
        'name' => 'required|string',
        ...Captcha::mathCaptchaRules(),
    ];
}
```

## 6. Advanced Configuration

### reCAPTCHA v3 with Custom Score

```php
// In your settings
'recaptcha_score' => 0.7, // Higher = stricter (0.0 to 1.0)
```

### Hide reCAPTCHA v3 Badge

```php
// In settings
'captcha_hide_badge' => true,
'captcha_show_disclaimer' => true, // Recommended if hiding badge
```

### Custom Math Captcha Difficulty

```php
// In config/captcha/general.php
return [
    'math-captcha' => [
        'operands' => ['+', '-', '*'],
        'rand-min' => 1,
        'rand-max' => 10, // Harder questions
    ],
];
```

## 7. Form-Specific Configuration

### Register Form Support

```php
use Modules\Captcha\Facades\Captcha;

// In a service provider
Captcha::registerFormSupport(
    \App\Forms\ContactForm::class,
    \App\Http\Requests\ContactRequest::class,
    'Contact Form'
);
```

### Enable for Specific Forms

Once registered, you can enable/disable captcha per form in settings.

## 8. Testing

### Test reCAPTCHA v2
1. Load a form with captcha
2. Check the checkbox
3. Submit the form
4. Verify validation works

### Test reCAPTCHA v3
1. Load a form (captcha is invisible)
2. Submit the form
3. Should validate automatically

### Test Math Captcha
1. Load a form with math captcha
2. Solve the equation
3. Submit with correct answer → Success
4. Submit with wrong answer → Error

## 9. Troubleshooting

### Captcha Not Displaying

**Check:**
- `enable_captcha` setting is enabled
- Site key and secret key are correct
- JavaScript is enabled in browser
- No JavaScript errors in console

**Solution:**
```bash
# Clear caches
php artisan config:clear
php artisan view:clear

# Check settings
php artisan tinker
>>> setting('enable_captcha')
>>> setting('captcha_site_key')
```

### Validation Always Failing

**Check:**
- Secret key is correct
- Domain is registered with Google
- Server can reach Google API
- Request contains captcha response

**Debug:**
```php
// In your controller
dd(request()->all()); // Check if g-recaptcha-response exists
```

### Math Captcha Not Working

**Check:**
- Session is working properly
- `enable_math_captcha` is enabled
- Input name is 'math-captcha'

**Solution:**
```bash
# Test session
php artisan tinker
>>> session()->put('test', 'value')
>>> session()->get('test')
```

## 10. Common Scenarios

### Login Form with reCAPTCHA
```php
// LoginRequest.php
public function rules(): array
{
    return [
        'email' => 'required|email',
        'password' => 'required',
        ...Captcha::rules(),
    ];
}
```

### Registration with Math Captcha
```php
// RegisterRequest.php
public function rules(): array
{
    return [
        'name' => 'required|string',
        'email' => 'required|email|unique:users',
        'password' => 'required|confirmed',
        ...Captcha::mathCaptchaRules(),
    ];
}
```

### Contact Form with Both Options
```php
// ContactRequest.php
public function rules(): array
{
    $rules = [
        'name' => 'required',
        'email' => 'required|email',
        'message' => 'required',
    ];
    
    if (Captcha::reCaptchaEnabled()) {
        $rules = array_merge($rules, Captcha::rules());
    }
    
    if (Captcha::mathCaptchaEnabled()) {
        $rules = array_merge($rules, Captcha::mathCaptchaRules());
    }
    
    return $rules;
}
```

## 11. API Usage

### Programmatic Verification
```php
use Modules\Captcha\Facades\Captcha;

$response = request()->input('g-recaptcha-response');
$clientIp = request()->ip();

if (Captcha::verify($response, $clientIp)) {
    // Valid
} else {
    // Invalid
}
```

### Check if Enabled
```php
if (Captcha::isEnabled()) {
    // Show captcha
}

if (Captcha::mathCaptchaEnabled()) {
    // Show math captcha
}
```

## 12. Best Practices

1. **Use reCAPTCHA v3 for Better UX** - It's invisible and doesn't interrupt users
2. **Set Appropriate Score Threshold** - Start with 0.5 and adjust based on spam
3. **Always Validate Server-Side** - Never trust client-side validation alone
4. **Show Clear Error Messages** - Help users understand validation failures
5. **Test Regularly** - Ensure captcha continues to work after updates
6. **Monitor Logs** - Check for failed verifications
7. **Use HTTPS** - Required for production reCAPTCHA
8. **Backup Option** - Consider having Math Captcha as fallback

## 13. Performance Tips

- **Lazy Load Scripts** - Only load when needed
- **Use v3 for High Traffic** - Less user friction
- **Cache Settings** - Avoid repeated database queries
- **CDN for Google Scripts** - Faster loading

## 14. Security Considerations

- **Never Expose Secret Key** - Keep it in `.env` or secure settings
- **Validate Server-Side** - Client validation can be bypassed
- **Use HTTPS** - Encrypt communication
- **Monitor Failed Attempts** - Detect potential attacks
- **Set Rate Limits** - Prevent brute force

## 15. Next Steps

- [ ] Configure your reCAPTCHA keys
- [ ] Test on a simple form
- [ ] Add to important forms (login, register, contact)
- [ ] Configure form-specific settings
- [ ] Monitor and adjust score threshold
- [ ] Set up error logging
- [ ] Test in production

## Support

For detailed documentation, see:
- `README.md` - Comprehensive guide
- `IMPLEMENTATION_SUMMARY.md` - Technical details
- `VERIFICATION_CHECKLIST.md` - Testing guide

## Quick Reference

```php
// Display
{!! Captcha::display() !!}

// Validation
...Captcha::rules()

// Check enabled
Captcha::isEnabled()

// Verify
Captcha::verify($response, $ip)

// Math Captcha
app('math-captcha')->label()
app('math-captcha')->input()
...Captcha::mathCaptchaRules()
```

---

**Ready to Start!** 🚀

Configure your keys and start protecting your forms from spam and bots!
