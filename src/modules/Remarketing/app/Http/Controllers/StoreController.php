<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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

        $data = $request->safe()->except('settings_raw');

        if ($request->filled('settings_raw')) {
            $decoded = json_decode($request->input('settings_raw'), true);
            $data['settings'] = is_array($decoded) ? $decoded : [];
        }

        $store->update($data);

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

    public function verify(StoreStoreRequest $request): JsonResponse
    {
        $domain = $request->input('domain');
        $host = parse_url($domain, PHP_URL_HOST) ?? $domain;

        $resolved = @gethostbynamel($host);

        if (empty($resolved)) {
            return response()->json(['message' => 'No se pudo resolver el DNS del dominio.'], 422);
        }

        return response()->json(['message' => 'Conexión verificada.']);
    }

    public function dnsCheck(Store $store): JsonResponse
    {
        $this->authorize('view', $store);

        $host = parse_url($store->domain, PHP_URL_HOST) ?? $store->domain;
        $result = $this->deliverabilityChecker->check($host);

        return response()->json([
            'domain' => $host,
            'spf' => $result['spf'],
            'dkim' => $result['dkim'],
            'dmarc' => $result['dmarc'],
            'details' => $result['details'],
        ]);
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
