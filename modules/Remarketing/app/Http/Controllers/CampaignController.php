<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Mailer\Models\MailerLang;
use Modules\Mailer\Services\MailerTemplateRendererService;
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

        [$stores, $templates, $segments, $languages] = $this->getFormData();

        return view('remarketing::campaigns.form', compact('stores', 'templates', 'segments', 'languages'));
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['send_mode', 'scheduled_at']);
        $data['mailer_template_id'] = $this->resolveMailerTemplateId($data['template_id'] ?? null);

        if ($request->input('send_mode') === 'now') {
            $data['status'] = 'scheduled';
            $data['scheduled_at'] = now();
        } elseif ($request->input('send_mode') === 'schedule' && $request->input('scheduled_at')) {
            $data['status'] = 'scheduled';
            $data['scheduled_at'] = $request->date('scheduled_at');
        } else {
            $data['status'] = 'draft';
        }

        Campaign::query()->create($data);

        return redirect()->route('remarketing.campaigns.index')
            ->with('success', 'Campaña creada correctamente.');
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        [$stores, $templates, $segments, $languages] = $this->getFormData();

        return view('remarketing::campaigns.form', compact('campaign', 'stores', 'templates', 'segments', 'languages'));
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $data = $request->safe()->except(['send_mode', 'scheduled_at']);
        $data['mailer_template_id'] = $this->resolveMailerTemplateId($data['template_id'] ?? null);

        if ($request->input('send_mode') === 'now') {
            $data['status'] = 'scheduled';
            $data['scheduled_at'] = now();
        } elseif ($request->input('send_mode') === 'schedule' && $request->input('scheduled_at')) {
            $data['status'] = 'scheduled';
            $data['scheduled_at'] = $request->date('scheduled_at');
        }

        $campaign->update($data);

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

    public function preview(Campaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $langId = request()->input('lang_id');
        $html = '';

        if ($campaign->mailer_template_id) {
            try {
                $html = MailerTemplateRendererService::renderEmailTemplate(
                    $campaign->mailerTemplate,
                    $this->preparePreviewVariables($campaign),
                    $langId ? (int) $langId : null
                );
            } catch (\Throwable $e) {
                Log::warning('Campaign preview render failed', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($html) && $campaign->template) {
            $html = $campaign->template->html_content ?? '';
        }

        return view('remarketing::campaigns.preview', compact('campaign', 'html'));
    }

    private function preparePreviewVariables(Campaign $campaign): array
    {
        $store = $campaign->store;

        return [
            'CUSTOMER_NAME' => 'Juan',
            'CUSTOMER_LASTNAME' => 'García',
            'CUSTOMER_EMAIL' => 'juan@example.com',
            'UNSUBSCRIBE_URL' => url('/r/unsubscribe/demo'),
            'STORE_NAME' => $store?->name ?? 'Tienda Demo',
            'STORE_DOMAIN' => $store?->domain ?? 'demo.example.com',
            'CAMPAIGN_NAME' => $campaign->name,
            '{{firstName}}' => 'Juan',
            '{{lastName}}' => 'García',
            '{{email}}' => 'juan@example.com',
            '{{unsubscribeUrl}}' => url('/r/unsubscribe/demo'),
            '{{UNSUBSCRIBE_URL}}' => url('/r/unsubscribe/demo'),
            '{{storeName}}' => $store?->name ?? 'Tienda Demo',
        ];
    }

    private function resolveMailerTemplateId(?int $templateId): ?int
    {
        if ($templateId === null) {
            return null;
        }

        $template = Template::query()->find($templateId);

        return $template?->mailer_template_id;
    }

    private function getFormData(): array
    {
        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $stores = Store::query()->whereIn('id', $storeIds)->get(['id', 'name']);
        $userId = $user->id;
        $templates = Template::query()
            ->where(function ($q) use ($storeIds, $userId) {
                $q->whereIn('store_id', $storeIds)
                    ->orWhere(function ($q2) use ($userId) {
                        $q2->where('visibility', 'user')
                            ->whereHas('store', fn ($s) => $s->where('user_id', $userId));
                    })
                    ->orWhere('visibility', 'global');
            })
            ->get(['id', 'name']);
        $segments = Segment::query()->whereIn('store_id', $storeIds)->get(['id', 'name']);
        $languages = MailerLang::available()->get(['id', 'title']);

        return [$stores, $templates, $segments, $languages];
    }
}
