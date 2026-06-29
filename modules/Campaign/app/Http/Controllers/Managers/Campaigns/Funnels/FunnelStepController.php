<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns\Funnels;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Models\Funnel\Funnel;
use Modules\Campaign\Models\Funnel\FunnelStep;
use Modules\Campaign\Models\Template\PageTemplate;
use Modules\Campaign\Models\Template\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * FunnelStepController — pasos de un funnel. Cada paso es una página editada con
 * el builder (kind=page), partiendo de las page-templates ya sembradas.
 * Portado de acellemail (Refactor\FunnelStepController), global.
 */
class FunnelStepController extends Controller
{
    private function denied()
    {
        abort(Response::HTTP_FORBIDDEN);
    }

    private function funnel(string $uid): Funnel
    {
        $funnel = Funnel::where('uid', $uid)->first();
        if (! $funnel || Gate::denies('update', $funnel)) {
            $this->denied();
        }

        return $funnel;
    }

    /** Añade un paso al funnel (opcionalmente desde una page-template). */
    public function store(Request $request, string $funnelUid): JsonResponse
    {
        $funnel = $this->funnel($funnelUid);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'sometimes|string',
            'template_uid' => 'sometimes|nullable|string',
        ]);

        $step = new FunnelStep;
        $step->funnel_id = $funnel->id;
        $step->name = $request->name;
        $step->type = in_array($request->type, FunnelStep::types(), true) ? $request->type : FunnelStep::TYPE_LANDING;
        $step->sort_order = (int) ($funnel->steps()->max('sort_order') ?? 0) + 1;
        $step->save();

        // Plantilla de partida: page-template elegida, o página en blanco.
        $template = null;
        if ($request->filled('template_uid')) {
            $pt = PageTemplate::findByUid($request->template_uid);
            $template = $pt?->template;
        }
        if (! $template) {
            $template = Template::createBuilderTemplate('default', $step->name, Template::DEFAULT_BUILDER_JSON, '<div></div>');
        }
        $step->setTemplate($template, $step->name);

        return response()->json([
            'status' => 'success',
            'message' => trans('campaign::funnels.flash.step_added'),
            'redirect' => route('manager.funnels.steps.builder', $step->uid),
        ]);
    }

    /** Galería de page-templates para elegir punto de partida del paso. */
    public function templateGallery(Request $request, string $uid): JsonResponse
    {
        $items = PageTemplate::query()->orderBy('created_at', 'desc')->limit(50)->get()
            ->map(fn ($pt) => [
                'uid' => $pt->uid,
                'name' => $pt->name,
                'thumb' => $pt->template?->getThumbnailUrl(),
            ]);

        return response()->json($items->values());
    }

    public function builder(Request $request, string $uid)
    {
        $step = FunnelStep::with('funnel')->where('uid', $uid)->first();
        if (! $step || Gate::denies('update', $step->funnel)) {
            $this->denied();
        }
        if (! $step->template) {
            return redirect()->route('manager.funnels.edit', $step->funnel->uid);
        }

        if ($request->isMethod('post')) {
            $validator = $step->template->updateBuilderContent($request->json, $request->content);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            return response()->json(['status' => 'success']);
        }

        return view('campaign::manager.funnels.step_builder', compact('step'));
    }

    public function preview(Request $request, string $uid)
    {
        $step = FunnelStep::where('uid', $uid)->first();
        if (! $step || ! $step->template) {
            return redirect()->back();
        }

        return response($step->template->getPreviewContent());
    }

    public function reorder(Request $request, string $funnelUid): JsonResponse
    {
        $funnel = $this->funnel($funnelUid);
        $order = (array) $request->input('order', []);
        foreach ($order as $i => $stepUid) {
            FunnelStep::where('funnel_id', $funnel->id)->where('uid', $stepUid)->update(['sort_order' => $i + 1]);
        }

        return response()->json(['status' => 'success', 'message' => trans('campaign::funnels.flash.reordered')]);
    }

    public function duplicate(Request $request, string $uid): JsonResponse
    {
        $step = FunnelStep::with('funnel', 'template')->where('uid', $uid)->first();
        if (! $step || Gate::denies('update', $step->funnel)) {
            $this->denied();
        }
        $new = $step->duplicateInto($step->funnel);
        $new->sort_order = (int) ($step->funnel->steps()->max('sort_order') ?? 0) + 1;
        $new->save();

        return response()->json(['status' => 'success', 'message' => trans('campaign::funnels.flash.step_added')]);
    }

    public function delete(Request $request, string $uid): JsonResponse
    {
        $step = FunnelStep::with('funnel')->where('uid', $uid)->first();
        if (! $step || Gate::denies('update', $step->funnel)) {
            $this->denied();
        }
        $step->deleteAndCleanup();

        return response()->json(['status' => 'success', 'message' => trans('campaign::funnels.flash.step_deleted')]);
    }
}
