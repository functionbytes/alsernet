<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Store;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $user = auth()->user();

        $stores = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->get();

        $storeIds = $stores->pluck('id');

        $customers = Customer::query()
            ->with('store')
            ->whereIn('store_id', $storeIds)
            ->when($request->input('search'), fn ($q, $s) => $q->where('email', 'like', "%{$s}%"))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('country'), fn ($q, $c) => $q->where('country', $c))
            ->when($request->input('rfm'), function ($q, $rfm): void {
                match ($rfm) {
                    'champions' => $q->where('rfm_recency', '>=', 4)->where('rfm_frequency', '>=', 4),
                    'at_risk' => $q->where('rfm_recency', '<=', 2)->where('rfm_frequency', '>=', 3),
                    'lost' => $q->where('rfm_recency', '<=', 2)->where('rfm_frequency', '<=', 2),
                    default => null,
                };
            })
            ->latest()
            ->paginate(20);

        return view('remarketing::customers.index', compact('customers', 'stores'));
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load('store');

        $messages = $customer->messages()
            ->with('campaign')
            ->latest('sent_at')
            ->limit(10)
            ->get();

        $events = $customer->events()
            ->latest('occurred_at')
            ->limit(30)
            ->get();

        $orders = $customer->orders()
            ->latest('placed_at')
            ->limit(20)
            ->get();

        $consentEvents = $customer->consentEvents()
            ->latest('occurred_at')
            ->get();

        $timeline = $this->buildTimeline($events, $orders, $messages, $consentEvents);

        return view('remarketing::customers.show', compact('customer', 'messages', 'events', 'orders', 'consentEvents', 'timeline'));
    }

    /**
     * Merge events, orders, messages and consent events into a unified timeline (desc by date).
     */
    private function buildTimeline(mixed $events, mixed $orders, mixed $messages, mixed $consentEvents): array
    {
        $items = [];

        foreach ($events as $e) {
            $items[] = [
                'kind' => 'event',
                'date' => $e->occurred_at,
                'icon' => 'fa-circle-dot',
                'tone' => 'primary',
                'title' => $e->type,
                'subtitle' => $this->summarizeProperties($e->properties ?? []),
                'meta' => null,
            ];
        }

        foreach ($orders as $o) {
            $items[] = [
                'kind' => 'order',
                'date' => $o->placed_at,
                'icon' => 'fa-shopping-bag',
                'tone' => 'success',
                'title' => 'Pedido '.($o->order_number ?: '#'.$o->id),
                'subtitle' => $o->status.' · '.number_format($o->total, 2).' '.$o->currency,
                'meta' => null,
            ];
        }

        foreach ($messages as $m) {
            $items[] = [
                'kind' => 'message',
                'date' => $m->sent_at ?? $m->created_at,
                'icon' => 'fa-envelope',
                'tone' => 'info',
                'title' => $m->subject,
                'subtitle' => 'Email '.$m->status.($m->campaign ? ' · '.$m->campaign->name : ''),
                'meta' => null,
            ];
        }

        foreach ($consentEvents as $ce) {
            $tone = in_array($ce->event_type, ['granted', 'confirmed'], true) ? 'success' : 'danger';

            $items[] = [
                'kind' => 'consent',
                'date' => $ce->occurred_at,
                'icon' => 'fa-shield-halved',
                'tone' => $tone,
                'title' => 'Consent '.$ce->event_type,
                'subtitle' => $ce->source.($ce->ip ? ' · '.$ce->ip : ''),
                'meta' => null,
            ];
        }

        usort($items, fn ($a, $b) => ($b['date']?->timestamp ?? 0) <=> ($a['date']?->timestamp ?? 0));

        return array_slice($items, 0, 80);
    }

    private function summarizeProperties(array $properties): string
    {
        $pieces = [];

        foreach (array_slice($properties, 0, 3) as $k => $v) {
            $pieces[] = $k.': '.(is_array($v) ? json_encode($v) : (string) $v);
        }

        return implode(' · ', $pieces);
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        // DSR delete: anonymize PII, preserve consent events for audit
        $customer->update([
            'email' => 'deleted-'.$customer->id.'@removed.invalid',
            'email_hash' => hash('sha256', 'deleted-'.$customer->id),
            'first_name' => null,
            'last_name' => null,
            'phone' => null,
            'attributes' => null,
            'tags' => null,
            'status' => 'unsubscribed',
        ]);

        $customer->delete();

        return redirect()->route('remarketing.customers.index')
            ->with('success', 'Datos del cliente eliminados conforme a GDPR/DSR.');
    }
}
