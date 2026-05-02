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

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

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

        return view('remarketing::customers.index', compact('customers'));
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load('store');

        $recentMessages = $customer->messages()
            ->with('campaign')
            ->latest('sent_at')
            ->limit(10)
            ->get();

        $timeline = $customer->events()
            ->latest('occurred_at')
            ->limit(30)
            ->get();

        $consentHistory = $customer->consentEvents()
            ->latest('occurred_at')
            ->get();

        return view('remarketing::customers.show', compact('customer', 'recentMessages', 'timeline', 'consentHistory'));
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
