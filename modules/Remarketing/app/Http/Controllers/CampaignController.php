<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Remarketing\Http\Requests\Web\ScheduleCampaignRequest;
use Modules\Remarketing\Http\Requests\Web\StoreCampaignRequest;
use Modules\Remarketing\Http\Requests\Web\UpdateCampaignRequest;
use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Segment;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Template;
use Modules\Remarketing\Services\CampaignService;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Campaign::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $campaigns = Campaign::query()
            ->with(['store', 'segment', 'template'])
            ->whereIn('store_id', $storeIds)
            ->latest()
            ->paginate(20);

        return view('remarketing::campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $this->authorize('create', Campaign::class);

        [$stores, $templates, $segments] = $this->getFormData();

        return view('remarketing::campaigns.create', compact('stores', 'templates', 'segments'));
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        Campaign::query()->create($request->validated());

        return redirect()->route('remarketing.campaigns.index')
            ->with('success', 'Campaña creada correctamente.');
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        [$stores, $templates, $segments] = $this->getFormData();

        return view('remarketing::campaigns.edit', compact('campaign', 'stores', 'templates', 'segments'));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $campaign->update($request->validated());

        return redirect()->route('remarketing.campaigns.index')
            ->with('success', 'Campaña actualizada correctamente.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('remarketing.campaigns.index')
            ->with('success', 'Campaña eliminada correctamente.');
    }

    public function schedule(ScheduleCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaignService->schedule($campaign, $request->date('scheduled_at'));

        return redirect()->route('remarketing.campaigns.index')
            ->with('success', 'Campaña programada correctamente.');
    }

    public function sendTest(Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        // Dispatch test send to authenticated user email
        if (class_exists(SendEmailJob::class)) {
            // TODO: create a test-send variant with flag is_test=true
            SendEmailJob::dispatch($campaign, auth()->user())
                ->onQueue('remarketing');
        }

        return redirect()->back()
            ->with('success', 'Email de prueba enviado a '.auth()->user()->email.'.');
    }

    public function cancel(Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaignService->cancel($campaign);

        return redirect()->route('remarketing.campaigns.index')
            ->with('success', 'Campaña cancelada correctamente.');
    }

    private function getFormData(): array
    {
        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $stores = Store::query()->whereIn('id', $storeIds)->get(['id', 'name']);
        $templates = Template::query()->whereIn('store_id', $storeIds)->orWhere('is_global', true)->get(['id', 'name']);
        $segments = Segment::query()->whereIn('store_id', $storeIds)->get(['id', 'name']);

        return [$stores, $templates, $segments];
    }
}
