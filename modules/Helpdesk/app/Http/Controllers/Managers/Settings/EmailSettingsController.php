<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\UpdateEmailSettingsRequest;
use Modules\Helpdesk\Models\Setting;

class EmailSettingsController extends Controller
{
    private const GROUP = 'email';

    private const DEFAULTS = [
        'email_from_name' => '',
        'email_from_address' => '',
        'email_reply_to' => '',
        'email_signature' => '',
        'outbound_enabled' => false,
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'inbound_enabled' => false,
        'imap_host' => '',
        'imap_port' => 993,
        'imap_username' => '',
        'imap_password' => '',
        'imap_encryption' => 'ssl',
        'imap_folder' => 'INBOX',
        'auto_create_tickets' => true,
    ];

    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('index');
        $this->middleware('can:helpdesk.settings.update')->only('update');
    }

    public function index(): View
    {
        $dbSettings = Setting::allAsArray(self::GROUP);
        $settings = array_merge(self::DEFAULTS, $dbSettings);

        // Mask stored passwords — never send plaintext to the view
        $hasSmtpPassword = ! empty($settings['smtp_password']);
        $hasImapPassword = ! empty($settings['imap_password']);
        $settings['smtp_password'] = '';
        $settings['imap_password'] = '';

        return view('helpdesk::settings.email.index', [
            'backups' => $settings,
            'hasSmtpPassword' => $hasSmtpPassword,
            'hasImapPassword' => $hasImapPassword,
        ]);
    }

    public function update(UpdateEmailSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Preserve existing passwords if no new value was submitted
        foreach (['smtp_password', 'imap_password'] as $field) {
            if (empty($validated[$field])) {
                unset($validated[$field]);
            }
        }

        // Normalize booleans from unchecked checkboxes
        foreach (['outbound_enabled', 'inbound_enabled', 'auto_create_tickets'] as $field) {
            $validated[$field] = isset($validated[$field]) && $validated[$field];
        }

        foreach ($validated as $key => $value) {
            Setting::set(self::GROUP.'.'.$key, $value, self::GROUP);
        }

        return back()->with('success', 'Configuración de email actualizada correctamente.');
    }
}
