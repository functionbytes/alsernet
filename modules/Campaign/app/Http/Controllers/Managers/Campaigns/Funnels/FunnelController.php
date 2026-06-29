<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Funnels;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\Funnel\Funnel;
use Symfony\Component\HttpFoundation\Response;

/**
 * FunnelController — CRUD de embudos. Portado de acellemail (Refactor\FunnelController),
 * global. Gestión de pasos en FunnelStepController. Se omiten products/domains/reportes.
 */
class FunnelController extends Controller
{
    private function denied()
    {
        abort(Response::HTTP_FORBIDDEN);
    }

    public function index(Request $request)
    {
        if (Gate::denies('read', new Funnel)) {
            $this->denied();
        }

        $stats = [
            'total' => Funnel::count(),
            'published' => Funnel::where('status', Funnel::STATUS_PUBLISHED)->count(),
            'draft' => Funnel::where('status', Funnel::STATUS_DRAFT)->count(),
        ];

        return view('campaign::manager.funnels.index', compact('stats'));
    }

    public function listing(Request $request)
    {
        if (Gate::denies('read', new Funnel)) {
            $this->denied();
        }

        $query = Funnel::query()->withCount('steps')->search($request->keyword);
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $funnels = $query
            ->orderBy($request->sort_order ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        return view('campaign::manager.funnels._list', compact('funnels'));
    }

    public function create(Request $request)
    {
        if (Gate::denies('create', new Funnel)) {
            $this->denied();
        }

        $lists = CampaignMaillist::orderBy('name')->get();

        return view('campaign::manager.funnels.create', compact('lists'));
    }

    public function store(Request $request): JsonResponse
    {
        if (Gate::denies('create', new Funnel)) {
            $this->denied();
        }

        $request->validate(['name' => 'required|string|max:255']);

        $funnel = Funnel::newDefault();
        $funnel->name = $request->name;
        if ($request->filled('mail_list_uid')) {
            $list = CampaignMaillist::where('uid', $request->mail_list_uid)->first();
            $funnel->mail_list_id = $list?->id;
        }
        $funnel->save();

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::funnels.flash.created'),
            'redirect' => route('manager.funnels.edit', $funnel->uid),
        ]);
    }

    /** Editor del funnel: gestor de pasos. */
    public function edit(Request $request, string $uid)
    {
        $funnel = Funnel::with('steps.template')->where('uid', $uid)->first();
        if (! $funnel || Gate::denies('update', $funnel)) {
            $this->denied();
        }

        return view('campaign::manager.funnels.edit', compact('funnel'));
    }

    public function update(Request $request, string $uid): JsonResponse
    {
        $funnel = Funnel::where('uid', $uid)->first();
        if (! $funnel || Gate::denies('update', $funnel)) {
            $this->denied();
        }

        $request->validate(['name' => 'sometimes|string|max:255', 'mail_list_uid' => 'sometimes|string']);
        if ($request->filled('name')) {
            $funnel->name = $request->name;
        }
        if ($request->filled('mail_list_uid')) {
            $funnel->mail_list_id = CampaignMaillist::where('uid', $request->mail_list_uid)->value('id');
        }
        $funnel->save();

        return response()->json(['status' => 'success', 'message' => trans('campaign::funnels.flash.saved')]);
    }

    public function publish(Request $request, string $uid): JsonResponse
    {
        return $this->setStatus($uid, Funnel::STATUS_PUBLISHED, 'published');
    }

    public function unpublish(Request $request, string $uid): JsonResponse
    {
        return $this->setStatus($uid, Funnel::STATUS_DRAFT, 'unpublished');
    }

    private function setStatus(string $uid, string $status, string $flash): JsonResponse
    {
        $funnel = Funnel::where('uid', $uid)->first();
        if (! $funnel || Gate::denies('update', $funnel)) {
            $this->denied();
        }
        $funnel->status = $status;
        $funnel->published_at = $status === Funnel::STATUS_PUBLISHED ? now() : null;
        $funnel->save();

        return response()->json(['status' => 'success', 'message' => trans('campaign::funnels.flash.'.$flash)]);
    }

    public function duplicate(Request $request, string $uid): JsonResponse
    {
        $funnel = Funnel::with('steps.template')->where('uid', $uid)->first();
        if (! $funnel || Gate::denies('create', new Funnel)) {
            $this->denied();
        }
        $funnel->duplicate();

        return response()->json(['status' => 'success', 'message' => trans('campaign::funnels.flash.duplicated')]);
    }

    public function delete(Request $request): JsonResponse
    {
        if (Gate::denies('delete', new Funnel)) {
            $this->denied();
        }

        $uids = is_array($request->uids) ? $request->uids : explode(',', (string) $request->uids);
        foreach ($uids as $uid) {
            $funnel = Funnel::where('uid', trim($uid))->first();
            if ($funnel && Gate::allows('delete', $funnel)) {
                $funnel->deleteAndCleanup();
            }
        }

        return response()->json(['status' => 'success', 'message' => trans('campaign::funnels.flash.deleted')]);
    }
}
