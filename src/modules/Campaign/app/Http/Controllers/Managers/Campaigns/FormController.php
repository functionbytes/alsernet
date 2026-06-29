<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * FormController — formularios de suscripción. Portado de acellemail
 * (Refactor\FormController), adaptado al módulo: global (sin customer-scope),
 * lista = CampaignMaillist, edición con el builder portado (kind=form).
 * Se omite la conexión a "websites" de acellemail (no aplica al módulo).
 */
class FormController extends Controller
{
    private function denied()
    {
        abort(Response::HTTP_FORBIDDEN);
    }

    public function index(Request $request)
    {
        if (Gate::denies('read', new Form)) {
            $this->denied();
        }

        $stats = [
            'total' => Form::count(),
            'published' => Form::where('status', Form::STATUS_PUBLISHED)->count(),
            'draft' => Form::where('status', Form::STATUS_DRAFT)->count(),
        ];

        return view('campaign::manager.forms.index', compact('stats'));
    }

    public function listing(Request $request)
    {
        if (Gate::denies('read', new Form)) {
            $this->denied();
        }

        $query = Form::query()->with('mailList')->search($request->keyword);
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $forms = $query
            ->orderBy($request->sort_order ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        return view('campaign::manager.forms._list', compact('forms'));
    }

    public function create(Request $request)
    {
        if (Gate::denies('create', new Form)) {
            $this->denied();
        }

        if ($request->isMethod('post')) {
            $form = Form::newDefault();
            try {
                $validator = $form->createFromArray($request->all());
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validator->errors()->toArray()], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('campaign::forms.flash.created'),
                'redirect' => route('manager.forms.builder', $form->uid),
            ]);
        }

        $lists = CampaignMaillist::orderBy('name')->get();

        return view('campaign::manager.forms.create', compact('lists'));
    }

    public function builder(Request $request, string $uid)
    {
        $form = Form::findByUid($uid);

        if (! $form || Gate::denies('update', $form)) {
            $this->denied();
        }

        if (! $form->template) {
            return redirect()->route('manager.forms.index')
                ->with('error', 'This form has no template assigned.');
        }

        if ($request->isMethod('post')) {
            $validator = $form->template->updateBuilderContent($request->json, $request->content);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            return response()->json(['status' => 'success']);
        }

        return view('campaign::manager.forms.builder', compact('form'));
    }

    public function settings(Request $request, string $uid): JsonResponse
    {
        $form = Form::findByUid($uid);
        if (! $form || Gate::denies('update', $form)) {
            $this->denied();
        }

        $request->validate(['name' => 'sometimes|string|max:255', 'mail_list_uid' => 'sometimes|string']);

        if ($request->filled('name')) {
            $form->name = $request->name;
        }
        if ($request->filled('mail_list_uid')) {
            $list = CampaignMaillist::where('uid', $request->mail_list_uid)->first();
            if ($list) {
                $form->mail_list_id = $list->id;
            }
        }
        $form->save();

        return response()->json(['status' => 'success', 'message' => trans('campaign::forms.flash.saved')]);
    }

    public function publish(Request $request): JsonResponse
    {
        return $this->setStatus($request, Form::STATUS_PUBLISHED, 'published');
    }

    public function unpublish(Request $request): JsonResponse
    {
        return $this->setStatus($request, Form::STATUS_DRAFT, 'unpublished');
    }

    private function setStatus(Request $request, string $status, string $flashKey): JsonResponse
    {
        $uids = is_array($request->uids) ? $request->uids : explode(',', (string) $request->uids);
        foreach ($uids as $uid) {
            $form = Form::findByUid(trim($uid));
            if ($form && Gate::allows('update', $form)) {
                $form->status = $status;
                $form->save();
            }
        }

        return response()->json(['status' => 'success', 'message' => trans('campaign::forms.flash.'.$flashKey)]);
    }

    public function delete(Request $request): JsonResponse
    {
        if (Gate::denies('delete', new Form)) {
            $this->denied();
        }

        $uids = is_array($request->uids) ? $request->uids : explode(',', (string) $request->uids);
        foreach ($uids as $uid) {
            $form = Form::findByUid(trim($uid));
            if ($form && Gate::allows('delete', $form)) {
                $form->deleteAndCleanup();
            }
        }

        return response()->json(['status' => 'success', 'message' => trans('campaign::forms.flash.deleted')]);
    }

    public function preview(Request $request, string $uid)
    {
        $form = Form::findByUid($uid);
        if (! $form || ! $form->template) {
            return redirect()->back();
        }
        if (Gate::denies('read', $form)) {
            $this->denied();
        }

        return response($form->template->getPreviewContent());
    }
}
