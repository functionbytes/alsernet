<?php

namespace Modules\Mailrelay\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\Lists;

class ListController extends Controller
{
    /**
     * Get all lists
     */
    public function index()
    {
        return response()->json(
            Lists::withCount('subscribers')->get()
        );
    }

    /**
     * Get a specific list
     */
    public function show(Lists $list)
    {
        return response()->json(
            $list->loadCount(['subscribers', 'campaigns'])
        );
    }

    /**
     * Create a new list
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mails_lists,name',
            'description' => 'nullable|string|max:1000',
        ]);

        $list = Lists::create($validated);

        return response()->json($list, 201);
    }

    /**
     * Update a list
     */
    public function update(Request $request, Lists $list)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mails_lists,name,'.$list->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $list->update($validated);

        return response()->json($list);
    }

    /**
     * Delete a list
     */
    public function destroy(Lists $list)
    {
        $list->delete();

        return response()->json(null, 204);
    }
}
