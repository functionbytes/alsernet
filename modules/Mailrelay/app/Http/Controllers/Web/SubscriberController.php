<?php

namespace Modules\Mailrelay\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Mailrelay\Entities\MailrelayGroup;
use Modules\Mailrelay\Entities\Subscriber;
use Modules\Mailrelay\Http\Requests\Web\StoreSubscriberRequest;
use Modules\Mailrelay\Http\Requests\Web\UpdateSubscriberRequest;
use Modules\Mailrelay\Jobs\SyncMailrelaySubscriberJob;
use Modules\Mailrelay\Services\EmailValidation\EmailValidatorService;

class SubscriberController extends Controller
{
    public function __construct(
        protected EmailValidatorService $validator
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Subscriber::query();

        // Apply filters
        if ($request->search) {
            $query->where('email', 'like', '%'.$request->search.'%')
                ->orWhere('name', 'like', '%'.$request->search.'%');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->sync_status === 'synced') {
            $query->whereNotNull('mailrelay_id');
        } elseif ($request->sync_status === 'pending') {
            $query->whereNull('mailrelay_id');
        }

        if ($request->validation === 'validated') {
            $query->whereNotNull('validated_at');
        } elseif ($request->validation === 'unvalidated') {
            $query->whereNull('validated_at');
        }

        // Get statistics (before pagination)
        $statsRaw = Subscriber::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as active', ['active', 'subscribed'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending', ['pending'])
            ->selectRaw('SUM(CASE WHEN mailrelay_id IS NOT NULL THEN 1 ELSE 0 END) as synced')
            ->first();

        $stats = [
            'total' => (int) $statsRaw->total,
            'active' => (int) $statsRaw->active,
            'pending' => (int) $statsRaw->pending,
            'synced' => (int) $statsRaw->synced,
        ];

        $subscribers = $query->latest()
            ->paginate(15);

        return view('mailrelay::subscribers.index', compact('subscribers', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $mailrelayGroups = MailrelayGroup::all();

        return view('mailrelay::subscribers.create', compact('mailrelayGroups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriberRequest $request)
    {
        $validated = $request->validated();

        $subscriber = Subscriber::create([
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'custom_fields' => $validated['custom_fields'] ?? null,
            'metadata' => $validated['metadata'] ? json_decode($validated['metadata'], true) : null,
            'subscribed_at' => now(),
            'validated_at' => $request->validate_email ? now() : null,
        ]);

        // Attach mailrelay groups
        if (! empty($validated['mailrelay_groups'])) {
            $subscriber->groups()->attach($validated['mailrelay_groups']);
        }

        // Queue Mailrelay sync
        if ($request->sync_to_mailrelay) {
            SyncMailrelaySubscriberJob::dispatch($subscriber->id);
        }

        return redirect()->route('subscribers.index')
            ->with('success', 'Subscriber created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $subscriber = Subscriber::with('groups')->findOrFail($id);

        return view('mailrelay::subscribers.show', compact('subscriber'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $subscriber = Subscriber::with('groups')->findOrFail($id);
        $mailrelayGroups = MailrelayGroup::all();

        return view('mailrelay::subscribers.edit', compact('subscriber', 'mailrelayGroups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscriberRequest $request, string $id)
    {
        $subscriber = Subscriber::findOrFail($id);

        $validated = $request->validated();

        $updateData = [
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'custom_fields' => $validated['custom_fields'] ?? null,
        ];

        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }

        if (isset($validated['metadata'])) {
            $updateData['metadata'] = $validated['metadata'] ? json_decode($validated['metadata'], true) : null;
        }

        $subscriber->update($updateData);

        // Sync mailrelay groups
        if (isset($validated['mailrelay_groups'])) {
            $subscriber->groups()->sync($validated['mailrelay_groups']);
        }

        // Queue Mailrelay sync if requested
        if ($request->sync_to_mailrelay) {
            SyncMailrelaySubscriberJob::dispatch($subscriber->id);
        }

        return redirect()->route('subscribers.index')
            ->with('success', 'Subscriber updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subscriber = Subscriber::findOrFail($id);
        $subscriber->delete();

        return redirect()->route('subscribers.index')
            ->with('success', 'Subscriber deleted successfully.');
    }
}
