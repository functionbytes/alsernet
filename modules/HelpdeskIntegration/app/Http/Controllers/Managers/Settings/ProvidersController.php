<?php

namespace Modules\HelpdeskIntegration\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskIntegration\Http\Requests\Managers\Settings\StoreIntegrationProviderRequest;
use Modules\HelpdeskIntegration\Http\Requests\Managers\Settings\UpdateIdentitySettingsRequest;
use Modules\HelpdeskIntegration\Http\Requests\Managers\Settings\UpdateIntegrationProviderRequest;
use Modules\HelpdeskIntegration\Models\IntegrationProvider;
use Modules\HelpdeskIntegration\Services\IntegrationProviderService;

class ProvidersController extends Controller
{
    public function __construct(
        private readonly IntegrationProviderService $providers,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', IntegrationProvider::class);

        abort_if(! helpdesk_integration_enabled(), 404);

        $providers = IntegrationProvider::query()->orderBy('sort_order')->orderBy('label')->get();
        $identitySmsEnabled = helpdesk_integration_identity_sms_enabled();

        return view('helpdeskintegration::settings.providers.index', compact('providers', 'identitySmsEnabled'));
    }

    public function updateIdentitySettings(UpdateIdentitySettingsRequest $request): RedirectResponse
    {
        Setting::set(
            'integration.identity_sms_enabled',
            $request->boolean('identity_sms_enabled') ? '1' : '0',
            'integration'
        );

        return redirect()
            ->route('settings.helpdeskintegration.providers.index')
            ->with('success', 'Configuración de verificación de identidad actualizada.');
    }

    public function create(): View
    {
        $this->authorize('create', IntegrationProvider::class);

        return view('helpdeskintegration::settings.providers.create');
    }

    public function store(StoreIntegrationProviderRequest $request): RedirectResponse
    {
        $this->providers->create($request->validated());

        return redirect()
            ->route('settings.helpdeskintegration.providers.index')
            ->with('success', __('helpdeskintegration::messages.providers.created'));
    }

    public function edit(IntegrationProvider $provider): View
    {
        $this->authorize('update', $provider);

        return view('helpdeskintegration::settings.providers.edit', compact('provider'));
    }

    public function update(UpdateIntegrationProviderRequest $request, IntegrationProvider $provider): RedirectResponse
    {
        $this->providers->update($provider, $request->validated());

        return redirect()
            ->route('settings.helpdeskintegration.providers.index')
            ->with('success', __('helpdeskintegration::messages.providers.updated'));
    }

    public function toggle(IntegrationProvider $provider): RedirectResponse
    {
        $this->authorize('update', $provider);

        $provider->update(['is_active' => ! $provider->is_active]);

        return redirect()
            ->route('settings.helpdeskintegration.providers.index')
            ->with('success', __('helpdeskintegration::messages.providers.updated'));
    }

    public function destroy(IntegrationProvider $provider): RedirectResponse
    {
        $this->authorize('delete', $provider);

        $this->providers->delete($provider);

        return redirect()
            ->route('settings.helpdeskintegration.providers.index')
            ->with('success', __('helpdeskintegration::messages.providers.deleted'));
    }
}
