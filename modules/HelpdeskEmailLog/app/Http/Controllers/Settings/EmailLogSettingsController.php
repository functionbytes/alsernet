<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Http\Requests\Settings\UpdateEmailLogSettingsRequest;

class EmailLogSettingsController extends Controller
{
    private const PREFIX = 'helpdeskemaillog.';

    public function __construct()
    {
        $this->middleware('can:helpdeskemaillog.settings.view')->only('index');
        $this->middleware('can:helpdeskemaillog.settings.update')->only('update');
    }

    public function index(): View
    {

        $s = fn (string $key, mixed $default = null) => Setting::get(self::PREFIX.$key, $default ?? config('helpdeskemaillog.'.$key));

        return view('helpdeskemaillog::settings.index', [
            'storeBody' => (bool) $s('store_body', true),
            'maxBodyKb' => (int) round((int) $s('max_body_bytes', 524288) / 1024),
            'retentionDays' => (int) $s('retention_days', 90),
            'staleQueuedHours' => (int) $s('stale_queued_hours', 24),
            'perPage' => (int) $s('per_page', 25),
            'perPageOptions' => config('helpdeskemaillog.per_page_options', [10, 25, 50, 100]),
        ]);
    }

    public function update(UpdateEmailLogSettingsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            Setting::set(self::PREFIX.'store_body', $request->has('store_body') ? '1' : '0');
            Setting::set(self::PREFIX.'max_body_bytes', (int) $request->validated()['max_body_bytes'] * 1024);
            Setting::set(self::PREFIX.'retention_days', $request->validated()['retention_days']);
            Setting::set(self::PREFIX.'stale_queued_hours', $request->validated()['stale_queued_hours']);
            Setting::set(self::PREFIX.'per_page', $request->validated()['per_page']);
        });

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->back()->with('success', 'Configuración del log de emails actualizada.');
    }
}
