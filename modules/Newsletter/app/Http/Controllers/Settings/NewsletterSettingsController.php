<?php

namespace Modules\Newsletter\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Newsletter\Http\Requests\NewsletterSettingsRequest;

class NewsletterSettingsController extends Controller
{
    public function index(): View
    {
        $settings = [
            'email_notifications' => Setting::get('newsletter.email_notifications', '0'),
            'popup_enabled' => Setting::get('newsletter.popup_enabled', '0'),
            'popup_title' => Setting::get('newsletter.popup_title', ''),
            'popup_subtitle' => Setting::get('newsletter.popup_subtitle', ''),
            'popup_description' => Setting::get('newsletter.popup_description', ''),
            'popup_delay' => Setting::get('newsletter.popup_delay', '5'),
            'mailjet_enabled' => Setting::get('newsletter.mailjet_enabled', '0'),
            'mailjet_api_key' => Setting::get('newsletter.mailjet_api_key', ''),
            'mailjet_api_secret' => Setting::get('newsletter.mailjet_api_secret', ''),
            'mailjet_list_id' => Setting::get('newsletter.mailjet_list_id', ''),
        ];

        return view('newsletter::settings.index', compact('settings'));
    }

    public function update(NewsletterSettingsRequest $request): RedirectResponse
    {
        $checkboxFields = ['email_notifications', 'popup_enabled', 'mailjet_enabled'];
        $textFields = ['popup_title', 'popup_subtitle', 'popup_description', 'popup_delay', 'mailjet_api_key', 'mailjet_api_secret', 'mailjet_list_id'];

        foreach ($checkboxFields as $field) {
            Setting::set("newsletter.{$field}", $request->has($field) ? '1' : '0');
        }

        foreach ($textFields as $field) {
            Setting::set("newsletter.{$field}", $request->input($field, ''));
        }

        return redirect()->route('settings.newsletter.index')->with('success', 'Configuración del newsletter actualizada.');
    }
}
