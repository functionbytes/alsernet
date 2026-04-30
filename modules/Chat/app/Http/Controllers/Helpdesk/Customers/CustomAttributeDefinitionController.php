<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Customers;

use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Customers\CustomerAttribute;

class CustomAttributeDefinitionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $model = $request->get('model', 'contact');

        $baseQuery = auth()->user()->account->customAttributeDefinitions();

        $customerCount = (clone $baseQuery)->where('attribute_model', CustomerAttribute::MODEL_CONTACT)->count();
        $conversationCount = (clone $baseQuery)->where('attribute_model', CustomerAttribute::MODEL_CONVERSATION)->count();

        $attributes = $baseQuery
            ->when($model, function ($query, $model) {
                $modelType = $model === 'contact'
                    ? CustomerAttribute::MODEL_CONTACT
                    : CustomerAttribute::MODEL_CONVERSATION;

                return $query->where('attribute_model', $modelType);
            })
            ->when($request->get('search'), function ($query, $search) {
                return $query->where('attribute_display_name', 'like', "%{$search}%");
            })
            ->orderBy('attribute_display_name')
            ->paginate(20);

        return view('Chat::settings.custom-attributes.index', compact('attributes', 'model', 'customerCount', 'conversationCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', CustomerAttribute::class);

        return view('Chat::settings.custom-attributes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', CustomerAttribute::class);

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

        return redirect()->route('settings.chat.custom-attributes.index')
            ->with('success', 'Custom attribute created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerAttribute $customAttribute)
    {
        $this->authorize('view', $customAttribute);

        return view('Chat::settings.custom-attributes.show', compact('customAttribute'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerAttribute $customAttribute)
    {
        $this->authorize('update', $customAttribute);

        return view('Chat::settings.custom-attributes.edit', compact('customAttribute'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerAttribute $customAttribute)
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

        return redirect()->route('settings.chat.custom-attributes.index')
            ->with('success', 'Custom attribute updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerAttribute $customAttribute)
    {
        $this->authorize('delete', $customAttribute);

        $customAttribute->delete();

        return redirect()->route('settings.chat.custom-attributes.index')
            ->with('success', 'Custom attribute deleted successfully.');
    }
}
