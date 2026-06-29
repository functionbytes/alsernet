<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Forms\Http\Requests\StoreFormFollowUpRequest;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormFollowUp;

class FormFollowUpController extends Controller
{
    public function index(Request $request, Form $form): View|JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $followUps = FormFollowUp::query()
            ->where('form_id', $form->id)
            ->orderBy('send_after_days')
            ->get();

        if ($request->expectsJson()) {
            return response()->json(['data' => $followUps]);
        }

        return view('forms::settings.forms.follow-ups.index', compact('form', 'followUps'));
    }

    public function store(StoreFormFollowUpRequest $request, Form $form): JsonResponse
    {
        $followUp = $form->followUps()->create($request->validated());

        return response()->json(['data' => $followUp], 201);
    }

    public function update(StoreFormFollowUpRequest $request, Form $form, FormFollowUp $followUp): JsonResponse
    {
        $followUp->update($request->validated());

        return response()->json(['data' => $followUp]);
    }

    public function destroy(Form $form, FormFollowUp $followUp): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $followUp->delete();

        return response()->json(['message' => 'Follow-up eliminado correctamente.']);
    }
}
