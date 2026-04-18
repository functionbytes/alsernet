<?php

namespace Modules\Newsletter\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Mailer\Models\MailerTemplate;
use Modules\Newsletter\Http\Requests\NewsletterSettingsRequest;

class NewsletterSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'email_notifications' => Setting::get('newsletter.email_notifications', '0'),
            'recaptcha_enabled' => Setting::get('newsletter.recaptcha_enabled', '0'),
            'popup_enabled' => Setting::get('newsletter.popup_enabled', '0'),
            'popup_delay' => Setting::get('newsletter.popup_delay', '5'),
            'mailjet_enabled' => Setting::get('newsletter.mailjet_enabled', '0'),
            'mailjet_api_key' => Setting::get('newsletter.mailjet_api_key', ''),
            'mailjet_api_secret' => Setting::get('newsletter.mailjet_api_secret', ''),
            'mailjet_list_id' => Setting::get('newsletter.mailjet_list_id', ''),
            'email_subscription_enabled' => Setting::get('newsletter.email_subscription_enabled', '0'),
            'email_subscription_template_id' => Setting::get('newsletter.email_subscription_template_id', ''),
            'email_unsubscription_enabled' => Setting::get('newsletter.email_unsubscription_enabled', '0'),
            'email_unsubscription_template_id' => Setting::get('newsletter.email_unsubscription_template_id', ''),
        ];

        $availableTemplates = MailerTemplate::where('module', 'newsletter')
            ->enabled()
            ->orderBy('name')
            ->get(['id', 'name', 'key'])
            ->map(fn ($t) => ['id' => $t->id, 'text' => $t->name])
            ->toArray();

        return view('newsletter::settings.index', compact('settings', 'availableTemplates'));
    }

    public function update(NewsletterSettingsRequest $request): RedirectResponse
    {
        $checkboxFields = [
            'email_notifications',
            'recaptcha_enabled',
            'popup_enabled',
            'mailjet_enabled',
            'email_subscription_enabled',
            'email_unsubscription_enabled',
        ];

        $textFields = [
            'popup_delay',
            'mailjet_api_key',
            'mailjet_api_secret',
            'mailjet_list_id',
            'email_subscription_template_id',
            'email_unsubscription_template_id',
        ];

        foreach ($checkboxFields as $field) {
            Setting::set("newsletter.{$field}", $request->has($field) ? '1' : '0');
        }

        foreach ($textFields as $field) {
            Setting::set("newsletter.{$field}", $request->input($field, ''));
        }

        return redirect()->route('settings.newsletter.index')->with('success', 'Configuración del newsletter actualizada.');
    }
}
