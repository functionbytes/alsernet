<?php

namespace Modules\Analytics\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class AnalyticsNotificationController extends Controller
{
    public function index(): View
    {
        $this->authorize('analytics.reports.view');

        return view('analytics::settings.notifications', [
            'sentEmails' => $this->decodeEmails(Setting::get('analytics_notification_sent_emails', '[]')),
            'failedEmails' => $this->decodeEmails(Setting::get('analytics_notification_failed_emails', '[]')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('analytics.reports.view');

        $request->validate([
            'sent_emails' => ['nullable', 'array'],
            'sent_emails.*' => ['email', 'max:255'],
            'failed_emails' => ['nullable', 'array'],
            'failed_emails.*' => ['email', 'max:255'],
        ]);

        $sentEmails = array_values(array_filter($request->input('sent_emails', [])));
        $failedEmails = array_values(array_filter($request->input('failed_emails', [])));

        Setting::set('analytics_notification_sent_enabled', count($sentEmails) > 0 ? '1' : '0');
        Setting::set('analytics_notification_sent_emails', json_encode($sentEmails));
        Setting::set('analytics_notification_failed_enabled', count($failedEmails) > 0 ? '1' : '0');
        Setting::set('analytics_notification_failed_emails', json_encode($failedEmails));

        return back()->with('success', 'Configuración de notificaciones guardada.');
    }

    private function decodeEmails(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? $decoded : [];
    }
}
