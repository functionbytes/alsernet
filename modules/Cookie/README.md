# Cookie Consent Module

GDPR-compliant cookie consent management module for Laravel applications.

## Features

- **GDPR Compliance**: Fully compliant with EU GDPR regulations
- **Cookie Categories**: Support for essential, analytics, and marketing cookies
- **Customizable**: Configurable appearance, messages, and behavior
- **Google Analytics Integration**: Built-in support for Google Analytics consent mode
- **Facebook Pixel Integration**: Support for Facebook Pixel with consent management
- **Two Display Styles**: Full-width banner or minimal popup
- **Responsive Design**: Works perfectly on mobile and desktop devices
- **RTL Support**: Full right-to-left language support
- **Theme Integration**: Easy integration with Laravel themes

## Installation

1. The module is already installed in `modules/Cookie`

2. Publish configuration files (optional):
```bash
php artisan vendor:publish --provider="Modules\Cookie\Providers\CookieServiceProvider" --tag="config"
```

3. Publish views (optional):
```bash
php artisan vendor:publish --provider="Modules\Cookie\Providers\CookieServiceProvider" --tag="views"
```

## Configuration

Edit `config/Cookie/general.php` to customize the module:

```php
return [
    'enabled' => true,
    'cookie_name' => 'cookie_for_consent',
    'cookie_lifetime' => 365 * 20, // 20 years
    'style' => 'full-width', // or 'minimal'
    'message' => 'Your experience on this site will be improved by allowing cookies.',
    'button_text' => 'Accept cookies',
    'show_reject_button' => false,
    'show_customize_button' => false,
    // ... more options
];
```

## Cookie Categories

The module supports three cookie categories:

### Essential Cookies
- Required for website functionality
- Cannot be disabled
- Includes session cookies and CSRF tokens

### Analytics Cookies
- Track website usage
- Optional - user can opt-out
- Supports Google Analytics consent mode

### Marketing Cookies
- Used for advertising
- Optional - user can opt-out
- Supports Facebook Pixel and other ad platforms

## Usage

### Basic Implementation

The module automatically injects the cookie consent banner into your frontend footer. No additional code is required.

### Checking User Consent

You can check if a user has consented to cookies in your code:

```php
use Illuminate\Support\Facades\Cookie;

$consent = Cookie::get('cookie_for_consent');
if ($consent) {
    $categories = json_decode($consent, true);

    if (isset($categories['analytics']) && $categories['analytics']) {
        // Load analytics scripts
    }

    if (isset($categories['marketing']) && $categories['marketing']) {
        // Load marketing scripts
    }
}
```

### JavaScript API

The module provides a JavaScript API:

```javascript
// Check if user has consented
if (window.Cookie.cookieExists('cookie_for_consent')) {
    // User has made a choice
}

// Get cookie value
const consent = window.Cookie.getCookie('cookie_for_consent');

// Programmatically hide dialog
window.Cookie.hideCookieDialog();

// Programmatically consent
window.Cookie.consentWithCookies();

// Programmatically reject
window.Cookie.rejectAllCookies();
```

## Google Analytics Integration

To enable Google Analytics consent mode:

1. Update your config:
```php
'google_analytics' => [
    'enabled' => true,
    'tracking_id' => 'G-XXXXXXXXXX',
],
```

2. Add Google Analytics to your layout:
```html
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
</script>
```

The module will automatically handle consent mode updates.

## Theme Options Integration

If you're using a theme with theme options support, you can enable cookie consent from your theme settings:

1. Navigate to Admin > Appearance > Theme Options
2. Find the "Cookie Consent" section
3. Enable cookie consent
4. Customize appearance and messages

## Customization

### Custom Styles

You can override the default styles by creating a custom CSS file:

```css
.cookie-consent {
    /* Your custom styles */
}
```

### Custom Messages

Edit language files in `modules/Cookie/resources/lang/en/cookie-consent.php` or publish them and edit in `resources/lang/modules/Cookie/`.

### Custom View

Publish the views and edit `resources/views/modules/Cookie/index.blade.php`.

## Asset Compilation

To compile assets after making changes:

1. Install dependencies:
```bash
cd modules/Cookie
npm install
```

2. Compile for development:
```bash
npm run dev
```

3. Compile for production:
```bash
npm run prod
```

## Environment Variables

You can configure the module using environment variables:

```env
COOKIE_CONSENT_ENABLED=true
COOKIE_CONSENT_NAME=cookie_for_consent
COOKIE_CONSENT_LIFETIME=7300
COOKIE_CONSENT_STYLE=full-width
COOKIE_CONSENT_SHOW_REJECT=false
COOKIE_CONSENT_SHOW_CUSTOMIZE=false
COOKIE_CONSENT_GA_ENABLED=false
GOOGLE_ANALYTICS_TRACKING_ID=G-XXXXXXXXXX
COOKIE_CONSENT_FB_PIXEL_ENABLED=false
FACEBOOK_PIXEL_ID=XXXXXXXXXX
```

## Seeding Cookie Policy Pages

Use the `HasCookieSeeder` trait in your seeder:

```php
use Modules\Cookie\Database\Traits\HasCookieSeeder;

class DatabaseSeeder extends Seeder
{
    use HasCookieSeeder;

    public function run()
    {
        // Create cookie policy page
        Page::create([
            'name' => $this->getCookiePageName(),
            'content' => $this->getCookiePageContent(),
            'slug' => 'cookie-policy',
        ]);
    }
}
```

## Support

For issues and feature requests, please contact the development team.

## License

This module is proprietary software developed for Inoqua Lab.
