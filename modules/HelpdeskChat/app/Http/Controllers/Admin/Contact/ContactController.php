<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Contact;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactAttribute;
use Modules\HelpdeskChat\Models\Contacts\ContactNote;
use Modules\HelpdeskChat\Models\Label;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Contact::where('account_id', auth()->user()->account_id)
            ->with(['conversation' => fn ($q) => $q->latest()->limit(1)]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $contacts = $query->paginate(50);

        return view('helpdeskchat::admin.contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('helpdeskchat::admin.contacts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'country_code' => 'nullable|string|max:5',
            'phone_number' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'company_name' => 'nullable|string|max:255',
            'social_profiles' => 'nullable|array',
            'social_profiles.linkedin' => 'nullable|url|max:500',
            'social_profiles.facebook' => 'nullable|url|max:500',
            'social_profiles.instagram' => 'nullable|url|max:500',
            'social_profiles.twitter' => 'nullable|url|max:500',
            'social_profiles.github' => 'nullable|url|max:500',
            'additional_attributes' => 'nullable|array',
        ]);

        $validated['account_id'] = auth()->user()->account_id;

        $contact = Contact::create($validated);

        return redirect()->route('admin.helpdesk.contacts.show', $contact)
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
        $this->authorize('view', $contact);

        $contact->load([
            'conversation.inbox',
            'conversation.assignee',
            'contactInboxes.inbox',
            'labels',
            'notes.user',
        ]);

        $accountLabels = Label::where('account_id', auth()->user()->account_id)
            ->orderBy('title')
            ->get();

        $customAttributes = ContactAttribute::forAccount(auth()->user()->account_id)
            ->forContacts()
            ->orderBy('attribute_display_name')
            ->get();

        return view('helpdeskchat::admin.contacts.show', compact('contact', 'accountLabels', 'customAttributes'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact)
    {
        $this->authorize('update', $contact);

        return view('helpdeskchat::admin.contacts.edit', compact('contact'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'country_code' => 'nullable|string|max:5',
            'phone_number' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:5000',
            'company_name' => 'nullable|string|max:255',
            'social_profiles' => 'nullable|array',
            'social_profiles.linkedin' => 'nullable|url|max:500',
            'social_profiles.facebook' => 'nullable|url|max:500',
            'social_profiles.instagram' => 'nullable|url|max:500',
            'social_profiles.twitter' => 'nullable|url|max:500',
            'social_profiles.github' => 'nullable|url|max:500',
            'additional_attributes' => 'nullable|array',
        ]);

        $contact->update($validated);

        return redirect()->route('admin.helpdesk.contacts.show', $contact)
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return redirect()->route('admin.helpdesk.contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }

    /**
     * Show import form.
     */
    public function importForm()
    {
        return view('helpdeskchat::admin.contacts.import');
    }

    /**
     * Import contacts from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

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
                $exists = Contact::where('account_id', $accountId)
                    ->where(function ($q) use ($data) {
                        if (! empty($data['email'])) {
                            $q->where('email', $data['email']);
                        }
                        if (! empty($data['phone_number'])) {
                            $q->orWhere('phone_number', $data['phone_number']);
                        }
                    })
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Contact::create([
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

        return redirect()->route('admin.helpdesk.contacts.index')
            ->with('success', "Imported {$imported} contacts. Skipped {$skipped} duplicates.");
    }

    /**
     * Export contacts to CSV.
     */
    public function export(Request $request)
    {
        $query = Contact::where('account_id', auth()->user()->account_id);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $contacts = $query->get();

        $filename = 'contacts_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($contacts) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['name', 'email', 'phone_number', 'created_at']);

            // Data rows
            foreach ($contacts as $contact) {
                fputcsv($file, [
                    $contact->name,
                    $contact->email,
                    $contact->phone_number,
                    $contact->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show merge form.
     */
    public function mergeForm(Request $request)
    {
        $request->validate([
            'contacts' => 'required|array|min:2',
            'contacts.*' => 'exists:contacts,id',
        ]);

        $contacts = Contact::whereIn('id', $request->contacts)
            ->where('account_id', auth()->user()->account_id)
            ->with(['conversation', 'contactInboxes'])
            ->get();

        if ($contacts->count() < 2) {
            return redirect()->back()
                ->withErrors(['contacts' => 'Please select at least 2 contacts to merge.']);
        }

        return view('helpdeskchat::admin.contacts.merge', compact('contacts'));
    }

    /**
     * Merge contacts.
     */
    public function merge(Request $request)
    {
        $request->validate([
            'primary_contact_id' => 'required|exists:contacts,id',
            'merge_contact_ids' => 'required|array|min:1',
            'merge_contact_ids.*' => 'exists:contacts,id',
        ]);

        $accountId = auth()->user()->account_id;
        $primaryContact = Contact::where('id', $request->primary_contact_id)
            ->where('account_id', $accountId)
            ->firstOrFail();

        $mergeContacts = Contact::whereIn('id', $request->merge_contact_ids)
            ->where('account_id', $accountId)
            ->get();

        DB::beginTransaction();

        try {
            foreach ($mergeContacts as $contact) {
                if ($contact->id === $primaryContact->id) {
                    continue;
                }

                // Move conversation to primary contact
                $contact->conversations()->update(['contact_id' => $primaryContact->id]);

                // Move contact inboxes to primary contact
                $contact->contactInboxes()->update(['contact_id' => $primaryContact->id]);

                // Delete the merged contact
                $contact->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['merge' => 'Merge failed: '.$e->getMessage()]);
        }

        return redirect()->route('admin.helpdesk.contacts.show', $primaryContact)
            ->with('success', 'Contacts merged successfully.');
    }

    /**
     * Block contact.
     */
    public function block(Contact $contact)
    {
        $this->authorize('update', $contact);

        $contact->block();

        return redirect()->back()
            ->with('success', 'Contact blocked successfully.');
    }

    /**
     * Unblock contact.
     */
    public function unblock(Contact $contact)
    {
        $this->authorize('update', $contact);

        $contact->unblock();

        return redirect()->back()
            ->with('success', 'Contact unblocked successfully.');
    }

    /**
     * Add label to contact.
     */
    public function addLabel(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $request->validate([
            'label_id' => 'required|exists:labels,id',
        ]);

        $label = Label::where('id', $request->label_id)
            ->where('account_id', auth()->user()->account_id)
            ->firstOrFail();

        $contact->labels()->syncWithoutDetaching([$label->id]);

        return redirect()->back()
            ->with('success', 'Label added successfully.');
    }

    /**
     * Remove label from contact.
     */
    public function removeLabel(Contact $contact, Label $label)
    {
        $this->authorize('update', $contact);

        $contact->labels()->detach($label->id);

        return redirect()->back()
            ->with('success', 'Label removed successfully.');
    }

    /**
     * Store a new note.
     */
    public function storeNote(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        ContactNote::create([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        return redirect()->back()
            ->with('success', 'Note added successfully.');
    }

    /**
     * Delete a note.
     */
    public function destroyNote(Contact $contact, ContactNote $note)
    {
        $this->authorize('update', $contact);

        if ($note->contact_id !== $contact->id) {
            abort(403);
        }

        $note->delete();

        return redirect()->back()
            ->with('success', 'Note deleted successfully.');
    }

    /**
     * Update custom attributes for a contact.
     */
    public function updateCustomAttributes(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        // Get all contact custom attribute definitions for this account
        $customAttributes = CustomAttributeDefinition::forAccount(auth()->user()->account_id)
            ->forContacts()
            ->get();

        // Build validation rules dynamically based on attribute types
        $rules = [];
        foreach ($customAttributes as $attribute) {
            $key = "custom_attributes.{$attribute->attribute_key}";

            switch ($attribute->attribute_display_type) {
                case CustomAttributeDefinition::DISPLAY_TYPE_TEXT:
                    $rules[$key] = 'nullable|string|max:500';
                    break;

                case CustomAttributeDefinition::DISPLAY_TYPE_NUMBER:
                case CustomAttributeDefinition::DISPLAY_TYPE_CURRENCY:
                case CustomAttributeDefinition::DISPLAY_TYPE_PERCENT:
                    $rules[$key] = 'nullable|numeric';
                    break;

                case CustomAttributeDefinition::DISPLAY_TYPE_LINK:
                    $rules[$key] = 'nullable|url|max:500';
                    break;

                case CustomAttributeDefinition::DISPLAY_TYPE_DATE:
                    $rules[$key] = 'nullable|date';
                    break;

                case CustomAttributeDefinition::DISPLAY_TYPE_LIST:
                    if ($attribute->attribute_values) {
                        $rules[$key] = 'nullable|in:'.implode(',', $attribute->attribute_values);
                    } else {
                        $rules[$key] = 'nullable|string';
                    }
                    break;

                case CustomAttributeDefinition::DISPLAY_TYPE_CHECKBOX:
                    $rules[$key] = 'nullable|boolean';
                    break;
            }

            // Add regex validation if specified
            if ($attribute->regex_pattern) {
                $rules[$key] = ($rules[$key] ?? 'nullable').'|regex:'.$attribute->regex_pattern;
            }
        }

        $validated = $request->validate($rules);

        // Merge existing additional_attributes with new custom_attributes
        $existingAttributes = $contact->additional_attributes ?? [];
        $newAttributes = $validated['custom_attributes'] ?? [];

        // Merge, letting new values override existing ones
        $mergedAttributes = array_merge($existingAttributes, $newAttributes);

        // Update contact
        $contact->update([
            'additional_attributes' => $mergedAttributes,
        ]);

        return redirect()->back()
            ->with('success', 'Custom attributes updated successfully.');
    }
}
