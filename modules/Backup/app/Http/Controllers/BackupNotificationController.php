<?php

namespace Modules\Backup\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class BackupNotificationController extends Controller
{
    public function index(): View
    {
        $this->authorize('Backup.backups.index');

        return view('backup::notifications.index', [
            'successEmails' => $this->decodeEmails(Setting::get('backup_notification_success_emails', '[]')),
            'failedEmails' => $this->decodeEmails(Setting::get('backup_notification_failed_emails', '[]')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('Backup.backups.index');

        $request->validate([
            'success_emails' => ['nullable', 'array'],
            'success_emails.*' => ['email', 'max:255'],
            'failed_emails' => ['nullable', 'array'],
            'failed_emails.*' => ['email', 'max:255'],
        ]);

        $successEmails = array_values(array_filter($request->input('success_emails', [])));
        $failedEmails = array_values(array_filter($request->input('failed_emails', [])));

        Setting::set('backup_notification_success_enabled', count($successEmails) > 0 ? '1' : '0');
        Setting::set('backup_notification_success_emails', json_encode($successEmails));
        Setting::set('backup_notification_failed_enabled', count($failedEmails) > 0 ? '1' : '0');
        Setting::set('backup_notification_failed_emails', json_encode($failedEmails));

        return back()->with('success', 'Configuración de notificaciones guardada.');
    }

    private function decodeEmails(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return is_array($decoded) ? $decoded : [];
    }
}
