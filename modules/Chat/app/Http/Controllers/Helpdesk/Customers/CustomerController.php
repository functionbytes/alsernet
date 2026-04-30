<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Customers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Http\Requests\Customer\AddLabelRequest;
use Modules\Chat\Http\Requests\Customer\ImportCustomersRequest;
use Modules\Chat\Http\Requests\Customer\MergeCustomersRequest;
use Modules\Chat\Http\Requests\Customer\MergeFormRequest;
use Modules\Chat\Http\Requests\Customer\StoreCustomerRequest;
use Modules\Chat\Http\Requests\Customer\StoreNoteRequest;
use Modules\Chat\Http\Requests\Customer\UpdateCustomAttributesRequest;
use Modules\Chat\Http\Requests\Customer\UpdateCustomerRequest;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerAttribute;
use Modules\Chat\Models\Customers\CustomerNote;
use Modules\Chat\Models\Labels\Label;
use Modules\Chat\Services\Customers\CustomerIndexService;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerIndexService $indexService
    ) {}

    /**
     * Resolve the correct route prefix based on the current request context.
     */
    private function routePrefix(): string
    {
        $routeName = request()->route()?->getName() ?? '';

        return str_starts_with($routeName, 'settings.chat.')
            ? 'settings.chat.customers'
            : 'chat.customers';
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = $this->indexService->buildIndexData(auth()->user()->account_id, $request);

        return view('Chat::chats.customers.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('Chat::chats.customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        $validated['account_id'] = auth()->user()->account_id;

        $customer = Customer::create($validated);

        return redirect()->route($this->routePrefix().'.show', $customer)
            ->with('success', 'Cliente creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load([
            'conversations.inbox',
            'conversations.assignee',
            'customerInboxes.inbox',
            'labels',
            'notes.user',
        ]);

        $accountLabels = ConversationLabel::forAccount(auth()->user()->account_id)
            ->orderBy('name')
            ->get();

        $customAttributes = CustomerAttribute::forAccount(auth()->user()->account_id)
            ->forCustomers()
            ->orderBy('attribute_display_name')
            ->get();

        return view('Chat::chats.customers.show', compact('customer', 'accountLabels', 'customAttributes'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        $customAttributes = CustomerAttribute::forAccount(auth()->user()->account_id)
            ->forCustomers()
            ->orderBy('attribute_display_name')
            ->get();

        return view('Chat::chats.customers.edit', compact('customer', 'customAttributes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $validated = $request->validated();

        // Handle custom attributes
        if ($request->has('custom_attributes')) {
            $existingAttributes = $customer->additional_attributes ?? [];
            $newAttributes = $request->input('custom_attributes', []);
            $validated['additional_attributes'] = array_merge($existingAttributes, $newAttributes);
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_url'] = '/storage/'.$path;
        }

        $customer->update($validated);

        return redirect()->route($this->routePrefix().'.show', $customer)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return redirect()->route($this->routePrefix().'.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }

    /**
     * Show import form.
     */
    public function importForm()
    {
        return view('Chat::chats.customers.import');
    }

    /**
     * Import contacts from CSV.
     */
    public function import(ImportCustomersRequest $request)
    {
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Read header row
        $header = fgetcsv($handle);

        if (! $header || ! in_array('name', $header)) {
            return redirect()->back()
                ->withErrors(['file' => 'CSV must contain a "name" column.']);
        }

        $accountId = auth()->user()->account_id;
        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);

                // Skip if name is empty
                if (empty($data['name'])) {
                    $skipped++;

                    continue;
                }

                // Check for duplicate email or phone
                $exists = Customer::forAccount($accountId)
                    ->duplicateContact($data['email'] ?? null, $data['phone_number'] ?? null)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Customer::create([
                    'account_id' => $accountId,
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'additional_attributes' => [],
                ]);

                $imported++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['file' => 'Import failed: '.$e->getMessage()]);
        } finally {
            fclose($handle);
        }

        return redirect()->route($this->routePrefix().'.index')
            ->with('success', "Importados {$imported} contactos. {$skipped} duplicados omitidos.");
    }

    /**
     * Export contacts to CSV.
     */
    public function export(Request $request)
    {
        $query = Customer::forAccount(auth()->user()->account_id);

        // Apply same filters as index
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $customers = $query->get();

        $filename = 'contacts_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['name', 'email', 'phone_number', 'created_at']);

            // Data rows
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->name,
                    $customer->email,
                    $customer->phone_number,
                    $customer->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show merge form.
     */
    public function mergeForm(MergeFormRequest $request)
    {
        $customers = Customer::forAccount(auth()->user()->account_id)
            ->whereIn('id', $request->contacts)
            ->with(['conversations', 'customerInboxes'])
            ->get();

        if ($customers->count() < 2) {
            return redirect()->back()
                ->withErrors(['contacts' => 'Please select at least 2 contacts to merge.']);
        }

        return view('Chat::chats.customers.merge', compact('customers'));
    }

    /**
     * Merge contacts.
     */
    public function merge(MergeCustomersRequest $request)
    {
        $accountId = auth()->user()->account_id;
        $primaryContact = Customer::forAccount($accountId)
            ->where('id', $request->primary_contact_id)
            ->firstOrFail();

        $mergeContacts = Customer::forAccount($accountId)
            ->whereIn('id', $request->merge_contact_ids)
            ->get();

        DB::beginTransaction();

        try {
            foreach ($mergeContacts as $customer) {
                if ($customer->id === $primaryContact->id) {
                    continue;
                }

                // Move conversations to primary customer
                $customer->conversations()->update(['customer_id' => $primaryContact->id]);

                // Move customer inboxes to primary customer
                $customer->customerInboxes()->update(['customer_id' => $primaryContact->id]);

                // Delete the merged contact
                $customer->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['merge' => 'Merge failed: '.$e->getMessage()]);
        }

        return redirect()->route($this->routePrefix().'.show', $primaryContact)
            ->with('success', 'Contactos fusionados correctamente.');
    }

    /**
     * Block contact.
     */
    public function block(Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->block();

        return redirect()->back()
            ->with('success', 'Customers blocked successfully.');
    }

    /**
     * Unblock contact.
     */
    public function unblock(Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer->unblock();

        return redirect()->back()
            ->with('success', 'Customers unblocked successfully.');
    }

    /**
     * Add label to contact.
     */
    public function addLabel(AddLabelRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $label = ConversationLabel::forAccount(auth()->user()->account_id)
            ->where('id', $request->label_id)
            ->firstOrFail();

        $customer->labels()->syncWithoutDetaching([$label->id]);

        return redirect()->back()
            ->with('success', 'Label added successfully.');
    }

    /**
     * Remove label from contact.
     */
    public function removeLabel(Customer $customer, Label $label)
    {
        $this->authorize('update', $customer);

        $customer->labels()->detach($label->id);

        return redirect()->back()
            ->with('success', 'Label removed successfully.');
    }

    /**
     * Store a new note.
     */
    public function storeNote(StoreNoteRequest $request, Customer $customer)
    {
        CustomerNote::create([
            'account_id' => auth()->user()->account_id,
            'customer_id' => $customer->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        return redirect()->back()
            ->with('success', 'Note added successfully.');
    }

    /**
     * Delete a note.
     */
    public function destroyNote(Customer $customer, CustomerNote $note)
    {
        $this->authorize('update', $customer);

        if ($note->customer_id !== $customer->id) {
            abort(403);
        }

        $note->delete();

        return redirect()->back()
            ->with('success', 'Note deleted successfully.');
    }

    /**
     * Update custom attributes for a contact.
     */
    public function updateCustomAttributes(UpdateCustomAttributesRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);
        $validated = $request->validated();

        // Merge existing additional_attributes with new custom_attributes
        $existingAttributes = $customer->additional_attributes ?? [];
        $newAttributes = $validated['custom_attributes'] ?? [];

        // Merge, letting new values override existing ones
        $mergedAttributes = array_merge($existingAttributes, $newAttributes);

        // Update contact
        $customer->update([
            'additional_attributes' => $mergedAttributes,
        ]);

        return redirect()->back()
            ->with('success', 'Custom attributes updated successfully.');
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
            ->route($this->routePrefix().'.show', $customer)
            ->with('success', 'Cliente restaurado correctamente.');
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
            ->route($this->routePrefix().'.index')
            ->with('success', "Cliente '{$name}' eliminado permanentemente.");
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
