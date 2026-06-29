<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreSlackIntegrationRequest;
use Modules\Helpdesk\Http\Requests\Settings\UpdateSlackIntegrationRequest;
use Modules\Helpdesk\Models\SlackIntegration;

class SlackIntegrationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.slack.view')->only(['index']);
        $this->middleware('can:helpdesk.slack.manage')->only(['create', 'store', 'edit', 'update', 'destroy', 'toggle']);
    }

    public function index(): View
    {
        $integrations = SlackIntegration::query()->latest()->paginate(20);

        $statsRow = SlackIntegration::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'active' => (int) $statsRow->active,
            'inactive' => (int) $statsRow->inactive,
        ];

        return view('helpdesk::settings.slack-integrations.index', compact('integrations', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.slack-integrations.create', [
            'integration' => null,
            'availableEvents' => SlackIntegration::AVAILABLE_EVENTS,
        ]);
    }

    public function store(StoreSlackIntegrationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        SlackIntegration::create($data);

        return redirect()
            ->route('settings.helpdesk.slack-integrations.index')
            ->with('success', 'Integracion de Slack creada exitosamente.');
    }

    public function edit(SlackIntegration $slackIntegration): View
    {
        return view('helpdesk::settings.slack-integrations.edit', [
            'integration' => $slackIntegration,
            'availableEvents' => SlackIntegration::AVAILABLE_EVENTS,
        ]);
    }

    public function update(UpdateSlackIntegrationRequest $request, SlackIntegration $slackIntegration): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        if (empty($data['webhook_url'])) {
            unset($data['webhook_url']);
        }

        $slackIntegration->update($data);

        return redirect()
            ->route('settings.helpdesk.slack-integrations.index')
            ->with('success', 'Integracion de Slack actualizada exitosamente.');
    }

    public function destroy(SlackIntegration $slackIntegration): RedirectResponse
    {
        $slackIntegration->delete();

        return redirect()
            ->route('settings.helpdesk.slack-integrations.index')
            ->with('success', 'Integracion de Slack eliminada exitosamente.');
    }

    public function toggle(SlackIntegration $slackIntegration): RedirectResponse
    {
        $slackIntegration->update(['is_active' => ! $slackIntegration->is_active]);

        $status = $slackIntegration->is_active ? 'activada' : 'desactivada';

        return redirect()
            ->route('settings.helpdesk.slack-integrations.index')
            ->with('success', "Integracion {$status} exitosamente.");
    }
}
