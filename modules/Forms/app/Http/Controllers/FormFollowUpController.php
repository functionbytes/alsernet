<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

    public function store(Request $request, Form $form): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $validated = $request->validate($this->rules());

        $followUp = $form->followUps()->create($validated);

        return response()->json(['data' => $followUp], 201);
    }

    public function update(Request $request, Form $form, FormFollowUp $followUp): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $validated = $request->validate($this->rules());

        $followUp->update($validated);

        return response()->json(['data' => $followUp]);
    }

    public function destroy(Form $form, FormFollowUp $followUp): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $followUp->delete();

        return response()->json(['message' => 'Follow-up eliminado correctamente.']);
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'send_after_days' => 'required|integer|min:1|max:365',
            'email_template_id' => 'nullable|integer',
            'recipient_type' => 'required|in:admin,submitter,both',
            'condition_field_key' => 'nullable|string|max:100',
            'condition_value' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }
}
