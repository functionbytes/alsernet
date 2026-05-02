<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Remarketing\Http\Requests\Web\StoreStoreRequest;
use Modules\Remarketing\Http\Requests\Web\UpdateStoreRequest;
use Modules\Remarketing\Jobs\SyncCatalogJob;
use Modules\Remarketing\Jobs\SyncCustomersJob;
use Modules\Remarketing\Jobs\SyncOrdersJob;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Services\DeliverabilityCheckerService;

class StoreController extends Controller
{
    public function __construct(
        private readonly DeliverabilityCheckerService $deliverabilityChecker
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();

        $stores = Store::query()
            ->with('user')
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->paginate(15);

        return view('remarketing::stores.index', compact('stores'));
    }

    public function create(): View
    {
        $this->authorize('create', Store::class);

        return view('remarketing::stores.create');
    }

    public function store(StoreStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['webhook_token'] = Str::random(64);

        $store = Store::query()->create($data);

        SyncCatalogJob::dispatch($store)->onQueue('remarketing');
        SyncCustomersJob::dispatch($store)->onQueue('remarketing');
        SyncOrdersJob::dispatch($store)->onQueue('remarketing');

        return redirect()->route('remarketing.stores.index')
            ->with('success', 'Tienda creada. La sincronización inicial se está procesando en segundo plano.');
    }

    public function edit(Store $store): View
    {
        $this->authorize('update', $store);

        return view('remarketing::stores.edit', compact('store'));
    }

    public function update(UpdateStoreRequest $request, Store $store): RedirectResponse
    {
        $this->authorize('update', $store);

        $store->update($request->validated());

        return redirect()->route('remarketing.stores.index')
            ->with('success', 'Tienda actualizada correctamente.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('delete', $store);

        $store->delete();

        return redirect()->route('remarketing.stores.index')
            ->with('success', 'Tienda eliminada correctamente.');
    }

    public function sync(Store $store): RedirectResponse
    {
        $this->authorize('update', $store);

        SyncCatalogJob::dispatch($store)->onQueue('remarketing');
        SyncCustomersJob::dispatch($store)->onQueue('remarketing');
        SyncOrdersJob::dispatch($store)->onQueue('remarketing');

        return redirect()->back()
            ->with('success', 'Sincronización iniciada. Los datos se actualizarán en breve.');
    }

    public function health(Store $store): View
    {
        $this->authorize('view', $store);

        $check = $this->deliverabilityChecker->check(
            parse_url($store->domain, PHP_URL_HOST) ?? $store->domain
        );

        return view('remarketing::stores.health', compact('store', 'check'));
    }
}
