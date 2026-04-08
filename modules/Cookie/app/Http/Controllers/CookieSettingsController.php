<?php

namespace Modules\Cookie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Cookie\Http\Requests\UpdateCookieSettingsRequest;
use Modules\Cookie\Models\CookieConsentLog;
use Modules\Core\Models\Setting;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CookieSettingsController extends Controller
{
    private const PREFIX = 'cookie.';

    public function __construct()
    {
        $this->middleware('can:Cookie.settings.index')->only('index', 'logs', 'export');
        $this->middleware('can:Cookie.settings.update')->only('update');
    }

    public function index(): View
    {
        $get = fn (string $key, mixed $default = '') => Setting::get(self::PREFIX.$key, $default);

        return view('cookie::settings.index', [
            'get' => $get,
        ]);
    }

    public function update(UpdateCookieSettingsRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();

        foreach (['enabled', 'google_analytics_enabled', 'facebook_pixel_enabled'] as $checkbox) {
            $data[$checkbox] = $request->has($checkbox) ? '1' : '0';
        }

        foreach ($data as $key => $value) {
            Setting::set(self::PREFIX.$key, $value ?? '');
        }

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()
            ->back()
            ->with('success', 'Configuración de cookies actualizada correctamente.');
    }

    public function logs(): View
    {
        $query = CookieConsentLog::with('user')->latest();

        if ($search = request('search')) {
            $query->where('action', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($from = request('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = request('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query->paginate(50)->withQueryString();

        $stats = [
            'total' => CookieConsentLog::count(),
            'accept_all' => CookieConsentLog::where('action', 'accept_all')->count(),
            'reject_all' => CookieConsentLog::where('action', 'reject_all')->count(),
            'custom' => CookieConsentLog::where('action', 'custom')->count(),
        ];

        return view('cookie::settings.logs', compact('logs', 'stats'));
    }

    public function export(): StreamedResponse
    {
        $filename = 'cookie-consents-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $columns = ['id', 'session_id', 'action', 'accepted_categories', 'ip_hash', 'user_id', 'created_at'];

        return response()->stream(function () use ($columns): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $columns);

            CookieConsentLog::query()
                ->cursor()
                ->each(function (CookieConsentLog $log) use ($handle): void {
                    fputcsv($handle, [
                        $log->id,
                        substr($log->session_id ?? '', 0, 8),
                        $log->action,
                        json_encode($log->accepted_categories),
                        substr($log->ip_hash ?? '', 0, 8),
                        $log->user_id,
                        $log->created_at,
                    ]);
                });

            fclose($handle);
        }, 200, $headers);
    }
}
