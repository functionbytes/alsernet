<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Conversation;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Customers\Customer;

class CustomersController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        // Authorize using Spatie Permission
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query();

        // Apply filters
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->tab === 'verified') {
            $query->verified();
        } elseif ($request->tab === 'banned') {
            $query->banned();
        } elseif ($request->tab === 'active') {
            $query->active();
        }

        // Sort and paginate with conversation count
        $customers = $query
            ->withCount('conversations')
            ->latest('created_at')
            ->paginate(50)
            ->appends($request->query());

        // Calculate statistics
        $stats = [
            'total' => Customer::count(),
            'verified' => Customer::verified()->count(),
            'banned' => Customer::banned()->count(),
            'active' => Customer::active()->count(),
            'total_conversations' => \DB::table('helpdesk_conversations')->count(),
            'new_this_month' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('helpdeskchat::admin.customers.index', [
            'customers' => $customers,
            'stats' => $stats,
            'tabs' => [
                'all' => $stats['total'],
                'verified' => $stats['verified'],
                'banned' => $stats['banned'],
                'active' => $stats['active'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        $this->authorize('create', Customer::class);

        return view('helpdeskchat::admin.customers.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:helpdesk_customers,email',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|string',
        ]);

        $customer = Customer::create($validated);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' created successfully.");
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        // Load relationships and counts
        $customer->load([
            'conversations' => fn ($q) => $q->latest()->limit(10),
            'sessions' => fn ($q) => $q->latest('created_at')->limit(5),
            'latestSession',
        ]);

        $customer->loadCount(['conversations as total_conversations', 'pageVisits as total_page_visits']);

        return view('helpdeskchat::admin.customers.show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        return view('helpdeskchat::admin.customers.edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:helpdesk_customers,email,{$customer->id}",
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' updated successfully.");
    }

    /**
     * Remove the specified customer from storage (soft delete).
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $name = $customer->name;
        $customer->delete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', "Customer '{$name}' deleted successfully.");
    }

    /**
     * Restore a soft-deleted customer.
     */
    public function restore($id)
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $this->authorize('restore', $customer);

        $customer->restore();

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer restored successfully.');
    }

    /**
     * Permanently delete a customer.
     */
    public function forceDelete($id)
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $customer);

        $name = $customer->name;
        $customer->forceDelete();

        return redirect()
            ->route('admin.customers.index')
            ->with('success', "Customer '{$name}' permanently deleted.");
    }

    /**
     * Ban a customer.
     */
    public function ban(Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->update([
            'is_banned' => true,
            'banned_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Cliente '{$customer->name}' suspendido correctamente.");
    }

    /**
     * Unban a customer.
     */
    public function unban(Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->update([
            'is_banned' => false,
            'banned_at' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', "Cliente '{$customer->name}' reactivado correctamente.");
    }
}
