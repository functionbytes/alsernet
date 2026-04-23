<?php

namespace Modules\Pulse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;

class PulseSettingsController extends Controller
{
    private const PREFIX = 'pulse.';

    private const RECORDERS = [
        'cache_interactions' => 'Cache Interactions',
        'exceptions' => 'Excepciones',
        'queues' => 'Colas',
        'slow_jobs' => 'Trabajos lentos',
        'slow_outgoing_requests' => 'Solicitudes salientes lentas',
        'slow_queries' => 'Consultas lentas',
        'slow_requests' => 'Solicitudes lentas',
        'user_jobs' => 'Trabajos de usuario',
        'user_requests' => 'Solicitudes de usuario',
    ];

    public function index(): View
    {
        $get = fn (string $key, string $default = '') => Setting::get(self::PREFIX.$key, $default);

        $settings = [
            'path' => $get('path', 'pulse'),
            'domain' => $get('domain', ''),
            'ingest_driver' => $get('ingest_driver', 'storage'),
            'ingest_buffer' => $get('ingest_buffer', '5000'),
            'storage_driver' => $get('storage_driver', 'database'),
            'storage_keep' => $get('storage_keep', '7 days'),
            'ingest_keep' => $get('ingest_keep', '7 days'),
            'slow_jobs_threshold' => $get('slow_jobs_threshold', '1000'),
            'slow_outgoing_requests_threshold' => $get('slow_outgoing_requests_threshold', '1000'),
            'slow_queries_threshold' => $get('slow_queries_threshold', '1000'),
            'slow_requests_threshold' => $get('slow_requests_threshold', '1000'),
            'server_name' => $get('server_name', gethostname()),
            'server_directories' => $get('server_directories', '/'),
        ];

        $recorders = [];
        foreach (self::RECORDERS as $key => $label) {
            $recorders[$key] = [
                'label' => $label,
                'enabled' => $get("recorder_{$key}_enabled", '1') === '1',
                'sample_rate' => (int) $get("recorder_{$key}_sample_rate", '1'),
            ];
        }

        return view('pulse::settings.index', compact('settings', 'recorders'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'path' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'ingest_driver' => 'required|in:storage,redis',
            'ingest_buffer' => 'required|integer|min:100|max:50000',
            'storage_driver' => 'required|in:database,redis',
            'storage_keep' => 'required|string|max:50',
            'ingest_keep' => 'required|string|max:50',
            'slow_jobs_threshold' => 'required|integer|min:100|max:60000',
            'slow_outgoing_requests_threshold' => 'required|integer|min:100|max:60000',
            'slow_queries_threshold' => 'required|integer|min:100|max:60000',
            'slow_requests_threshold' => 'required|integer|min:100|max:60000',
            'server_name' => 'required|string|max:255',
            'server_directories' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($request, $validated): void {
            foreach ($validated as $key => $value) {
                Setting::set(self::PREFIX.$key, $value);
            }

            foreach (self::RECORDERS as $key => $label) {
                Setting::set(
                    self::PREFIX."recorder_{$key}_enabled",
                    $request->has("recorder_{$key}_enabled") ? '1' : '0'
                );
                Setting::set(
                    self::PREFIX."recorder_{$key}_sample_rate",
                    (int) $request->input("recorder_{$key}_sample_rate", 1)
                );
            }
        });

        Setting::clearPrefixCache('pulse.');

        return redirect()->back()->with('success', 'Configuración de Pulse guardada correctamente.');
    }
}
