<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Automations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Domain\Automation\Enum\TriggerKey;
use Modules\Campaign\Models\Automation\Automation2;
use Modules\Campaign\Models\CampaignMaillist;
use Symfony\Component\HttpFoundation\Response;

/**
 * AutomationController — listado + asistente de creación de automatizaciones.
 * Portado de acellemail (Refactor\AutomationController), adaptado al módulo
 * (global, sin customer-scope; mail_list = CampaignMaillist). El editor de flujo
 * (nodos DAG) se añade en el Increment 2.
 */
class AutomationController extends Controller
{
    private function denied()
    {
        abort(Response::HTTP_FORBIDDEN);
    }

    /** Iconos por trigger (Material Symbols). */
    private function triggerIcon(string $type): string
    {
        return [
            'welcome-new-subscriber' => 'waving_hand',
            'say-goodbye-subscriber' => 'logout',
            'say-happy-birthday' => 'cake',
            'subscriber-added-date' => 'event',
            'specific-date' => 'calendar_month',
            'api-3-0' => 'api',
            'weekly-recurring' => 'date_range',
            'monthly-recurring' => 'calendar_view_month',
            'tag-based' => 'sell',
            'remove-tag' => 'label_off',
            'attribute-update' => 'edit_note',
            'woo-abandoned-cart' => 'shopping_cart',
        ][$type] ?? 'bolt';
    }

    private function triggers()
    {
        return collect(TriggerKey::cases())->map(function ($case) {
            $type = $case->value;

            return [
                'key' => $type,
                'icon' => $this->triggerIcon($type),
                'name' => trans('campaign::automations.trigger.'.$type),
                'desc' => trans('campaign::automations.trigger.'.$type.'_desc'),
            ];
        });
    }

    public function index(Request $request)
    {
        if (Gate::denies('read', Automation2::class)) {
            $this->denied();
        }

        $total = Automation2::count();
        $active = Automation2::where('status', Automation2::STATUS_ACTIVE)->count();
        $inactive = Automation2::where('status', Automation2::STATUS_INACTIVE)->count();

        return view('campaign::manager.flow-automations.index', compact('total', 'active', 'inactive'));
    }

    public function listing(Request $request)
    {
        if (Gate::denies('read', Automation2::class)) {
            $this->denied();
        }

        $query = Automation2::query()->search($request->keyword);

        if (! empty($request->status)) {
            $query->where('status', $request->status);
        }

        $automations = $query
            ->orderBy($request->sort_order ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 25)
            ->withQueryString();

        return view('campaign::manager.flow-automations._list', compact('automations'));
    }

    public function create(Request $request)
    {
        if (Gate::denies('create', new Automation2)) {
            $this->denied();
        }

        $lists = CampaignMaillist::orderBy('name')->get();

        return view('campaign::manager.flow-automations.create', [
            'triggers' => $this->triggers(),
            'lists' => $lists,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('create', new Automation2)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $automation = Automation2::newDefault();

        try {
            $validator = $automation->createFromArray($request->all());
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()->toArray()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::automations.flash.created'),
            'url' => route('manager.flow_automations.edit', $automation->uid),
        ]);
    }

    /** AJAX: campos de opciones específicos del trigger. */
    public function triggerOptions(Request $request)
    {
        return view('campaign::manager.flow-automations._trigger_options', [
            'triggerType' => $request->trigger_type,
            'lists' => CampaignMaillist::orderBy('name')->get(),
        ]);
    }

    /** AJAX: segmentos de una lista. */
    public function segmentSelect(Request $request): JsonResponse
    {
        $list = CampaignMaillist::where('uid', $request->list_uid)->first();
        if (! $list) {
            return response()->json([]);
        }

        $segments = $list->segments()->orderBy('name')->get()
            ->map(fn ($s) => ['uid' => $s->uid, 'name' => $s->name]);

        return response()->json($segments->values());
    }

    public function delete(Request $request): JsonResponse
    {
        if (Gate::denies('delete', new Automation2)) {
            $this->denied();
        }

        $uids = is_array($request->uids) ? $request->uids : explode(',', (string) $request->uids);
        foreach ($uids as $uid) {
            $a = Automation2::findByUid(trim($uid));
            if ($a) {
                $a->deleteAndCleanup();
            }
        }

        return response()->json(['status' => 'success', 'message' => trans('campaign::automations.flash.deleted')]);
    }

    /** Editor de flujo DAG (Increment 2). */
    public function edit(Request $request, string $uid)
    {
        $automation = Automation2::findByUid($uid);

        if (! $automation || Gate::denies('update', $automation)) {
            $this->denied();
        }

        $flow = $automation->getFlow()->toArray();
        $ctx = [
            'triggerKeys' => collect(TriggerKey::cases())->map(fn ($c) => [
                'key' => $c->value,
                'name' => trans('campaign::automations.trigger.'.$c->value),
            ])->values(),
        ];

        return view('campaign::manager.flow-automations.edit', compact('automation', 'flow', 'ctx'));
    }

    public function enable(Request $request): JsonResponse
    {
        $automation = Automation2::findByUid($request->uid ?? $request->route('uid'));
        if (! $automation || Gate::denies('update', $automation)) {
            $this->denied();
        }
        $automation->enable();

        return response()->json(['status' => 'success']);
    }

    public function disable(Request $request): JsonResponse
    {
        $automation = Automation2::findByUid($request->uid ?? $request->route('uid'));
        if (! $automation || Gate::denies('update', $automation)) {
            $this->denied();
        }
        $automation->disable();

        return response()->json(['status' => 'success']);
    }
}
