<?php

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;

class ThemeOptionController extends Controller
{
    private const PREFIX = 'theme.option.';

    private static array $repeatableKeys = [
        'social_links',
        'header_messages',
        'contact_boxes',
        'extra_fonts',
    ];

    /** @var list<string> */
    private static array $allowedKeys = [
        'preloader_enabled',
        'cta_floating_enabled',
        'phone',
        'email',
        'address',
        'opening_hours',
        'site_title',
        'seo_title',
        'seo_description',
        'seo_index',
        'og_image',
        'logo',
        'logo_light',
        'favicon',
        'copyright',
        'social_links',
        'header_messages',
        'contact_boxes',
        'primary_font',
        'secondary_font',
        'extra_fonts',
        'header_style',
        'homepage_id',
        'breadcrumb_enabled',
        'breadcrumb_bg_image',
        'fb_page_id',
        'fb_app_id',
        'fb_admins',
        'fb_comments_enabled',
        'fb_chat_enabled',
        'blog_layout',
        'blog_page_id',
        'blog_posts_per_page',
        'product_list_layout',
        'products_per_page',
        'related_products_count',
        'max_price_filter',
        'payment_methods_image',
        'checkout_logo',
        'login_bg_image',
        'register_bg_image',
        'return_policy_url',
        'return_policy_days',
        'newsletter_popup_enabled',
        'newsletter_popup_image',
        'newsletter_popup_title',
        'newsletter_popup_subtitle',
        'newsletter_popup_description',
        'newsletter_popup_delay',
        'newsletter_popup_show_on',
        'cookie_consent_enabled',
        'cookie_consent_style',
        'cookie_consent_message',
        'cookie_consent_btn_text',
        'cookie_consent_more_info_text',
        'cookie_consent_more_info_url',
        'cookie_consent_reject_btn',
        'cookie_consent_preferences_btn',
        'cookie_consent_max_width',
    ];

    public function index(): View
    {
        $this->authorize('template.custom-code');

        $o = fn (string $key, string $default = '') => Setting::get(self::PREFIX.$key, $default);

        return view('template::theme-options.index', [
            'o' => $o,
            'socialLinks' => json_decode($o('social_links', '[]'), true) ?: [],
            'headerMessages' => json_decode($o('header_messages', '[]'), true) ?: [],
            'contactBoxes' => json_decode($o('contact_boxes', '[]'), true) ?: [],
            'extraFonts' => json_decode($o('extra_fonts', '[]'), true) ?: [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('template.custom-code');

        $data = $request->only(self::$allowedKeys);

        foreach ($data as $key => $value) {
            if (in_array($key, self::$repeatableKeys, true)) {
                Setting::set(self::PREFIX.$key, json_encode($value));
            } else {
                Setting::set(self::PREFIX.$key, $value ?? '');
            }
        }

        // Clear cache for all theme options
        Setting::clearPrefixCache('theme.option');

        return redirect()
            ->back()
            ->with('success', 'Opciones del tema actualizadas correctamente.');
    }
}
