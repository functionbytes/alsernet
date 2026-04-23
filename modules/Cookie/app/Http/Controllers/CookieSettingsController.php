<?php

namespace Modules\Cookie\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Cookie\Http\Requests\UpdateCookieSettingsRequest;
use Modules\Cookie\Models\CookieConsentLog;
use Modules\Core\Models\Setting;
use Modules\Page\Models\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CookieSettingsController extends Controller
{
    private const PREFIX = 'cookie.';

    public function __construct()
    {
        $this->middleware('can:cookie.settings.view')->only('index', 'logs', 'export');
        $this->middleware('can:cookie.settings.update')->only('update');
    }

    public function index(): View
    {
        $get = fn (string $key, mixed $default = '') => Setting::get(self::PREFIX.$key, $default);

        $pages = Page::query()
            ->where('status', 'published')
            ->orderBy('title')
            ->limit(500)
            ->get(['id', 'title', 'slug']);

        return view('cookie::settings.index', [
            'get' => $get,
            'pages' => $pages,
        ]);
    }

    public function update(UpdateCookieSettingsRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();

        $checkboxes = [
            'enabled',
            'google_analytics_enabled',
            'facebook_pixel_enabled',
            'google_tag_manager_enabled',
            'microsoft_uet_enabled',
            'linkedin_insight_enabled',
            'tiktok_pixel_enabled',
            'twitter_pixel_enabled',
            'geo_targeting_enabled',
        ];

        foreach ($checkboxes as $checkbox) {
            $data[$checkbox] = $request->input($checkbox) === '1' ? '1' : '0';
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

        $rawStats = CookieConsentLog::query()
            ->selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');

        $stats = [
            'total' => $rawStats->sum(),
            'accept_all' => $rawStats->get('accept_all', 0),
            'reject_all' => $rawStats->get('reject_all', 0),
            'custom' => $rawStats->get('custom', 0),
        ];

        $trend = CookieConsentLog::query()
            ->selectRaw('DATE(created_at) as date, action, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date', 'action')
            ->orderBy('date')
            ->limit(31)
            ->get()
            ->groupBy('date')
            ->map(fn ($group) => [
                'date' => $group->first()->date,
                'accept_all' => $group->firstWhere('action', 'accept_all')?->total ?? 0,
                'reject_all' => $group->firstWhere('action', 'reject_all')?->total ?? 0,
                'custom' => $group->firstWhere('action', 'custom')?->total ?? 0,
            ])
            ->values();

        return view('cookie::settings.logs', compact('logs', 'stats', 'trend'));
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
