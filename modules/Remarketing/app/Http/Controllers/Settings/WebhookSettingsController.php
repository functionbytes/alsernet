<?php

namespace Modules\Remarketing\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Remarketing\Http\Requests\Settings\StoreWebhookRequest;
use Modules\Remarketing\Http\Requests\Settings\UpdateWebhookRequest;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Webhook;

class WebhookSettingsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Webhook::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $webhooks = Webhook::query()
            ->with('store')
            ->whereIn('store_id', $storeIds)
            ->latest()
            ->paginate(20);

        $stores = Store::query()->whereIn('id', $storeIds)->get(['id', 'name']);

        return view('remarketing::settings.webhooks', compact('webhooks', 'stores'));
    }

    public function store(StoreWebhookRequest $request): RedirectResponse
    {
        Webhook::query()->create($request->validated());

        return redirect()->route('settings.remarketing.webhooks.index')
            ->with('success', 'Webhook creado correctamente.');
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active');

        $webhook->update($data);

        return redirect()->route('settings.remarketing.webhooks.index')
            ->with('success', 'Webhook actualizado correctamente.');
    }

    public function destroy(Webhook $webhook): RedirectResponse
    {
        $this->authorize('delete', $webhook);

        $webhook->delete();

        return redirect()->route('settings.remarketing.webhooks.index')
            ->with('success', 'Webhook eliminado correctamente.');
    }
}
