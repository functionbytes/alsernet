<?php

namespace Modules\Mailrelay\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\Lists;
use Modules\Mailrelay\Services\MailRelayService;

class CampaignWebController extends Controller
{
    public function __construct(
        protected MailRelayService $mailrelay
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $campaigns = Campaign::query()
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(15);

        return view('mailrelay::campaigns.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $lists = Lists::all();

        return view('mailrelay::campaigns.create', compact('lists'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'list_id' => 'nullable|exists:mails_lists,id',
            'sender_id' => 'nullable|integer',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $campaign = Campaign::create($validated);

        // Create in Mailrelay if auto_sync enabled
        if (config('mailrelay.sync.auto_sync')) {
            $this->mailrelay->createCampaign($campaign);
        }

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $campaign = Campaign::with('analytics')->findOrFail($id);

        return view('mailrelay::campaigns.show', compact('campaign'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->isSent()) {
            return redirect()->route('campaigns.show', $id)
                ->with('error', 'Cannot edit a sent campaign.');
        }

        $lists = Lists::all();

        return view('mailrelay::campaigns.edit', compact('campaign', 'lists'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->isSent()) {
            return redirect()->route('campaigns.index')
                ->with('error', 'Cannot edit a sent campaign.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'list_id' => 'nullable|exists:mails_lists,id',
            'sender_id' => 'nullable|integer',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $campaign->update($validated);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->isSent()) {
            return redirect()->route('campaigns.index')
                ->with('error', 'Cannot delete a sent campaign.');
        }

        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }
}
