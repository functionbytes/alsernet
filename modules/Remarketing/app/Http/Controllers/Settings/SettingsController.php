<?php

namespace Modules\Remarketing\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Core\Models\Setting;
use Modules\Remarketing\Http\Requests\Settings\UpdateConsentSettingsRequest;
use Modules\Remarketing\Http\Requests\Settings\UpdateGeneralSettingsRequest;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Suppression;

class SettingsController extends Controller
{
    private const PREFIX = 'remarketing.';

    public function general(): View
    {
        $this->authorize('viewAny', Suppression::class);

        $get = fn (string $key, mixed $default = null) => Setting::get(self::PREFIX.$key, $default);

        return view('remarketing::settings.general', compact('get'));
    }

    public function updateGeneral(UpdateGeneralSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data as $key => $value) {
            Setting::set(self::PREFIX.$key, $value);
        }

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->route('settings.remarketing.general')
            ->with('success', 'Configuración general actualizada correctamente.');
    }

    public function consent(): View
    {
        $this->authorize('viewAny', Suppression::class);

        $get = fn (string $key, mixed $default = null) => Setting::get(self::PREFIX.$key, $default);

        return view('remarketing::settings.consent', compact('get'));
    }

    public function updateConsent(UpdateConsentSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data as $key => $value) {
            Setting::set(self::PREFIX.$key, $value);
        }

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()->route('settings.remarketing.consent')
            ->with('success', 'Configuración de consent actualizada correctamente.');
    }

    public function suppressions(): View
    {
        $this->authorize('viewAny', Suppression::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $suppressions = Suppression::query()
            ->with('store')
            ->whereIn('store_id', $storeIds)
            ->latest()
            ->paginate(30);

        return view('remarketing::settings.suppressions', compact('suppressions'));
    }
}
