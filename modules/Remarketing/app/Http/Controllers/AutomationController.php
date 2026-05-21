<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Remarketing\Http\Requests\Web\StoreAutomationRequest;
use Modules\Remarketing\Http\Requests\Web\UpdateAutomationRequest;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Template;

class AutomationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Automation::class);

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $automations = Automation::query()
            ->with(['store', 'steps'])
            ->whereIn('store_id', $storeIds)
            ->latest()
            ->paginate(20);

        return view('remarketing::automations.index', compact('automations'));
    }

    public function create(): View
    {
        $this->authorize('create', Automation::class);

        $stores = $this->getUserStores();

        return view('remarketing::automations.edit', compact('stores'));
    }

    public function store(StoreAutomationRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('steps');
        $steps = $request->input('steps', []);

        DB::transaction(function () use ($data, $steps): void {
            $automation = Automation::query()->create($data);
            foreach ($steps as $step) {
                $automation->steps()->create([
                    'sort_order' => $step['sort_order'] ?? 0,
                    'type' => $step['type'],
                    'config' => $step['config'] ?? [],
                    'mailer_template_id' => $this->resolveStepMailerTemplateId($step),
                ]);
            }
        });

        return redirect()->route('remarketing.automations.index')
            ->with('success', 'Automatización creada correctamente.');
    }

    public function edit(Automation $automation): View
    {
        $this->authorize('update', $automation);

        $automation->load('steps');
        $stores = $this->getUserStores();

        return view('remarketing::automations.edit', compact('automation', 'stores'));
    }

    public function update(UpdateAutomationRequest $request, Automation $automation): RedirectResponse
    {
        $this->authorize('update', $automation);

        $data = $request->safe()->except('steps');
        $steps = $request->input('steps', []);

        DB::transaction(function () use ($automation, $data, $steps): void {
            $automation->update($data);
            $automation->steps()->delete();
            foreach ($steps as $step) {
                $automation->steps()->create([
                    'sort_order' => $step['sort_order'] ?? 0,
                    'type' => $step['type'],
                    'config' => $step['config'] ?? [],
                    'mailer_template_id' => $this->resolveStepMailerTemplateId($step),
                ]);
            }
        });

        return redirect()->route('remarketing.automations.index')
            ->with('success', 'Automatización actualizada correctamente.');
    }

    public function destroy(Automation $automation): RedirectResponse
    {
        $this->authorize('delete', $automation);

        $automation->delete();

        return redirect()->route('remarketing.automations.index')
            ->with('success', 'Automatización eliminada correctamente.');
    }

    public function activate(Automation $automation): RedirectResponse
    {
        $this->authorize('update', $automation);

        $automation->update(['status' => 'active']);

        return redirect()->back()
            ->with('success', 'Automatización activada correctamente.');
    }

    public function pause(Automation $automation): RedirectResponse
    {
        $this->authorize('update', $automation);

        $automation->update(['status' => 'paused']);

        return redirect()->back()
            ->with('success', 'Automatización pausada correctamente.');
    }

    private function resolveStepMailerTemplateId(array $step): ?int
    {
        if (($step['type'] ?? '') !== 'send_email') {
            return null;
        }

        $templateId = $step['config']['template_id'] ?? null;
        if ($templateId === null) {
            return null;
        }

        $template = Template::query()->find($templateId);

        return $template?->mailer_template_id;
    }

    private function getUserStores(): Collection
    {
        $user = auth()->user();

        return Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->get(['id', 'name']);
    }
}
