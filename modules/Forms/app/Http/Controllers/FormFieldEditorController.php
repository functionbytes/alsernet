<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Forms\Http\Requests\UpdateFormFieldEditorRequest;
use Modules\Forms\Models\FormField;

class FormFieldEditorController extends Controller
{
    public function update(UpdateFormFieldEditorRequest $request, FormField $field): JsonResponse
    {
        $this->authorize('Forms.forms.edit');

        $field->load('form');

        $data = array_filter(
            $request->validated(),
            fn ($value) => $value !== null
        );

        $field->update($data);

        Cache::forget('forms:active:'.$field->form_id);
        Cache::forget('forms:active:slug:'.$field->form->slug);

        return response()->json(['success' => true, 'message' => 'Campo actualizado']);
    }
}
