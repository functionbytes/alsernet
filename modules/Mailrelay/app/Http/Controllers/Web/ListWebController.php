<?php

namespace Modules\Mailrelay\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\Lists;

class ListWebController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Lists::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $lists = $query->withCount('subscribers')
            ->latest()
            ->paginate(15);

        return view('mailrelay::lists.index', compact('lists'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('mailrelay::lists.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mails_lists,name',
            'description' => 'nullable|string|max:1000',
        ]);

        Lists::create($validated);

        return redirect()->route('mailrelay.lists.index')
            ->with('success', 'List created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lists $list)
    {
        $list->loadCount('subscribers', 'campaigns');

        return view('mailrelay::lists.show', compact('list'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lists $list)
    {
        return view('mailrelay::lists.edit', compact('list'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lists $list)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:mails_lists,name,'.$list->id,
            'description' => 'nullable|string|max:1000',
        ]);

        $list->update($validated);

        return redirect()->route('mailrelay.lists.index')
            ->with('success', 'List updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lists $list)
    {
        $list->delete();

        return redirect()->route('mailrelay.lists.index')
            ->with('success', 'List deleted successfully.');
    }
}
