<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings\Contact;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Contacts\ContactAttribute;

class CustomAttributeDefinitionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $model = $request->get('model', 'contact');

        $attributes = auth()->user()->account->customAttributeDefinitions()
            ->when($model, function ($query, $model) {
                $modelType = $model === 'contact'
                    ? ContactAttribute::MODEL_CONTACT
                    : ContactAttribute::MODEL_CONVERSATION;

                return $query->where('attribute_model', $modelType);
            })
            ->orderBy('attribute_display_name')
            ->paginate(20);

        return view('helpdeskchat::admin.settings.custom-attributes.index', compact('attributes', 'model'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('helpdeskchat::admin.settings.custom-attributes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'attribute_display_name' => 'required|string|max:255',
            'attribute_key' => 'required|string|max:255',
            'attribute_description' => 'nullable|string',
            'attribute_display_type' => 'required|integer|min:0|max:7',
            'attribute_model' => 'required|integer|min:0|max:1',
            'attribute_values' => 'nullable|array',
            'regex_pattern' => 'nullable|string',
            'regex_cue' => 'nullable|string',
        ]);

        auth()->user()->account->customAttributeDefinitions()->create($validated);

        return redirect()->route('admin.helpdesk.custom-attributes.index')
            ->with('success', 'Custom attribute created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomAttributeDefinition $customAttribute)
    {
        $this->authorize('view', $customAttribute);

        return view('helpdeskchat::admin.settings.custom-attributes.show', compact('customAttribute'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomAttributeDefinition $customAttribute)
    {
        $this->authorize('update', $customAttribute);

        return view('helpdeskchat::admin.settings.custom-attributes.edit', compact('customAttribute'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomAttributeDefinition $customAttribute)
    {
        $this->authorize('update', $customAttribute);

        $validated = $request->validate([
            'attribute_display_name' => 'required|string|max:255',
            'attribute_key' => 'required|string|max:255',
            'attribute_description' => 'nullable|string',
            'attribute_display_type' => 'required|integer|min:0|max:7',
            'attribute_values' => 'nullable|array',
            'regex_pattern' => 'nullable|string',
            'regex_cue' => 'nullable|string',
        ]);

        $customAttribute->update($validated);

        return redirect()->route('admin.helpdesk.custom-attributes.index')
            ->with('success', 'Custom attribute updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomAttributeDefinition $customAttribute)
    {
        $this->authorize('delete', $customAttribute);

        $customAttribute->delete();

        return redirect()->route('admin.helpdesk.custom-attributes.index')
            ->with('success', 'Custom attribute deleted successfully.');
    }
}
