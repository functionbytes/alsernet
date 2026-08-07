<?php

namespace Modules\MailsSettings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Core\Models\Setting;

class EmailSettingsController extends Controller
{
    /**
     * Display email backups selection page
     */
    public function index(): View
    {
        abort_unless(
            auth()->user()?->canAny(['mails-settings.outgoing.view', 'mails-settings.incoming.view']),
            403
        );

        $pageTitle = 'Configuración de Email';
        $breadcrumb = 'Configuración / Email';

        // Get basic backups info for display
        $outgoingSettings = Setting::getEmailSettings();
        $incomingSettings = Setting::getIncomingEmailSettings();

        return view('mails-settings::settings.index', compact(
            'pageTitle',
            'breadcrumb',
            'outgoingSettings',
            'incomingSettings'
        ));
    }
}
