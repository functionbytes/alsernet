<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Contact;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Jobs\BulkAddLabelToContacts;
use Modules\HelpdeskChat\Jobs\BulkDeleteContacts;
use Modules\HelpdeskChat\Jobs\BulkRemoveLabelFromContacts;
use Modules\HelpdeskChat\Models\Contacts\Contact;

class BulkContactController extends Controller
{
    /**
     * Add labels to multiple contacts.
     */
    public function addLabels(Request $request)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
            'label_ids' => 'required|array',
            'label_ids.*' => 'exists:labels,id',
        ]);

        BulkAddLabelToContacts::dispatch(
            auth()->user()->account_id,
            $validated['contact_ids'],
            $validated['label_ids']
        );

        return response()->json([
            'message' => 'Labels are being added to selected contacts',
            'count' => count($validated['contact_ids']),
        ]);
    }

    /**
     * Remove labels from multiple contacts.
     */
    public function removeLabels(Request $request)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
            'label_ids' => 'required|array',
            'label_ids.*' => 'exists:labels,id',
        ]);

        BulkRemoveLabelFromContacts::dispatch(
            auth()->user()->account_id,
            $validated['contact_ids'],
            $validated['label_ids']
        );

        return response()->json([
            'message' => 'Labels are being removed from selected contacts',
            'count' => count($validated['contact_ids']),
        ]);
    }

    /**
     * Delete multiple contacts.
     */
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        // Verify all contacts belong to user's account
        $contacts = Contact::whereIn('id', $validated['contact_ids'])
            ->where('account_id', auth()->user()->account_id)
            ->pluck('id')
            ->toArray();

        if (count($contacts) !== count($validated['contact_ids'])) {
            return response()->json([
                'error' => 'Some contacts do not belong to your account',
            ], 403);
        }

        BulkDeleteContacts::dispatch(
            auth()->user()->account_id,
            $contacts
        );

        return response()->json([
            'message' => 'Contacts are being deleted',
            'count' => count($contacts),
        ]);
    }

    /**
     * Export multiple contacts to CSV.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $contacts = Contact::whereIn('id', $validated['contact_ids'])
            ->where('account_id', auth()->user()->account_id)
            ->with(['contactInboxes.inbox'])
            ->get();

        // Generate CSV
        $filename = 'contacts_export_'.now()->format('Y-m-d_His').'.csv';
        $filepath = 'exports/'.$filename;

        $csv = fopen('php://temp', 'w');

        // Headers
        fputcsv($csv, [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Company',
            'City',
            'Country',
            'Created At',
            'Last Activity',
        ]);

        // Data
        foreach ($contacts as $contact) {
            fputcsv($csv, [
                $contact->id,
                $contact->full_name,
                $contact->email,
                $contact->phone_number,
                $contact->company_name,
                $contact->city,
                $contact->country,
                $contact->created_at->format('Y-m-d H:i:s'),
                $contact->last_activity_at?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        Storage::put($filepath, $csvContent);

        return response()->json([
            'message' => 'Export completed',
            'download_url' => Storage::url($filepath),
            'count' => count($contacts),
        ]);
    }
}
