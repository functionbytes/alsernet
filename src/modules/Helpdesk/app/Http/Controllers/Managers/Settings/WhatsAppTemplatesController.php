<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Jobs\SyncWhatsAppTemplatesJob;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;

class WhatsAppTemplatesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.whatsapp-templates.view')->only(['index']);
        $this->middleware('can:helpdesk.whatsapp-templates.manage')->only(['sync']);
    }

    public function index(Request $request): View
    {
        $query = WhatsAppTemplate::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('body_template', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $templates = $query->latest()->paginate(20);

        $stats = [
            'total' => WhatsAppTemplate::query()->count(),
            'approved' => WhatsAppTemplate::query()->where('status', 'approved')->count(),
            'pending' => WhatsAppTemplate::query()->where('status', 'pending')->count(),
            'rejected' => WhatsAppTemplate::query()->where('status', 'rejected')->count(),
        ];

        return view('helpdesk::settings.whatsapp-templates.index', compact('templates', 'stats'));
    }

    public function sync(): RedirectResponse
    {
        SyncWhatsAppTemplatesJob::dispatch();

        return redirect()
            ->route('settings.helpdesk.whatsapp-templates.index')
            ->with('success', 'Sincronizacion iniciada. Los templates se actualizaran en breve.');
    }
}
