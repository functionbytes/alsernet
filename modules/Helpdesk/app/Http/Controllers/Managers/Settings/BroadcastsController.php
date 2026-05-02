<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Settings\StoreBroadcastRequest;
use Modules\Helpdesk\Jobs\SendBroadcastJob;
use Modules\Helpdesk\Models\Campaigns\Broadcast;
use Modules\Helpdesk\Models\Campaigns\BroadcastRecipient;
use Modules\Helpdesk\Models\Customer;

class BroadcastsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.broadcasts.view')->only(['index', 'show']);
        $this->middleware('can:helpdesk.broadcasts.manage')->only(['create', 'store', 'destroy', 'send']);
    }

    public function index(Request $request): View
    {
        $query = Broadcast::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        $broadcasts = $query->latest()->paginate(15);

        $statsRow = Broadcast::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count,
            SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
            SUM(COALESCE(recipients_count, 0)) as recipients_total
        ')->first();

        $stats = [
            'total' => (int) $statsRow->total,
            'draft' => (int) $statsRow->draft_count,
            'sent' => (int) $statsRow->sent_count,
            'failed' => (int) $statsRow->failed_count,
            'recipients_total' => (int) $statsRow->recipients_total,
        ];

        return view('helpdesk::settings.broadcasts.index', compact('broadcasts', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.broadcasts.create');
    }

    public function store(StoreBroadcastRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();

        Broadcast::create($data);

        return redirect()
            ->route('settings.helpdesk.broadcasts.index')
            ->with('success', 'Broadcast creado en borrador exitosamente.');
    }

    public function show(Broadcast $broadcast): View
    {
        $recipients = $broadcast->recipients()->latest()->paginate(20);

        return view('helpdesk::settings.broadcasts.show', compact('broadcast', 'recipients'));
    }

    public function destroy(Broadcast $broadcast): RedirectResponse
    {
        if (! $broadcast->isDraft()) {
            return redirect()
                ->route('settings.helpdesk.broadcasts.index')
                ->with('error', 'Solo se pueden eliminar broadcasts en estado borrador.');
        }

        $broadcast->delete();

        return redirect()
            ->route('settings.helpdesk.broadcasts.index')
            ->with('success', 'Broadcast eliminado exitosamente.');
    }

    public function send(Broadcast $broadcast): RedirectResponse
    {
        if (! $broadcast->isDraft()) {
            return redirect()
                ->route('settings.helpdesk.broadcasts.show', $broadcast)
                ->with('error', 'Solo se pueden enviar broadcasts en estado borrador.');
        }

        $customers = $this->resolveRecipients($broadcast);

        if ($customers->isEmpty()) {
            return redirect()
                ->route('settings.helpdesk.broadcasts.show', $broadcast)
                ->with('error', 'No se encontraron destinatarios para este broadcast.');
        }

        $rows = $customers->map(fn (Customer $c) => [
            'broadcast_id' => $broadcast->id,
            'customer_id' => $c->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        BroadcastRecipient::query()->insertOrIgnore($rows);

        $broadcast->update([
            'status' => 'scheduled',
            'recipients_count' => count($rows),
        ]);

        SendBroadcastJob::dispatch($broadcast);

        return redirect()
            ->route('settings.helpdesk.broadcasts.show', $broadcast)
            ->with('success', 'Broadcast encolado. Los mensajes se enviarán en breve.');
    }

    /**
     * Resolve the customer list for a broadcast.
     * Filters are reserved for future use; for now we fetch all active customers
     * that have the contact field required by the channel.
     *
     * @return Collection<int, Customer>
     */
    private function resolveRecipients(Broadcast $broadcast): Collection
    {
        $query = Customer::query()->active();

        // TODO: apply $broadcast->filters when filter engine is implemented.

        return match ($broadcast->channel) {
            'whatsapp' => $query->where(function ($q) {
                $q->whereNotNull('whatsapp_phone')->orWhereNotNull('phone');
            })->get(),
            'email' => $query->whereNotNull('email')->get(),
            'facebook' => $query->whereNotNull('facebook_psid')->get(),
            'instagram' => $query->whereNotNull('instagram_id')->get(),
            'web' => $query->get(),
            default => collect(),
        };
    }
}
