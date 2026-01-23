<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Contact;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Services\Contacts\ContactMergeService;

class ContactMergeController extends Controller
{
    public function __construct(
        protected ContactMergeService $mergeService
    ) {}

    /**
     * Show the contact merge UI.
     */
    public function index(): View
    {
        return view('helpdeskchat::admin.contacts.merge.index');
    }

    /**
     * Find duplicate contacts.
     */
    public function findDuplicates(Request $request): JsonResponse
    {
        $request->validate([
            'skip_email' => 'boolean',
            'skip_phone' => 'boolean',
            'include_name_similarity' => 'boolean',
        ]);

        $accountId = Auth::user()->account_id;
        $criteria = $request->only(['skip_email', 'skip_phone', 'include_name_similarity']);

        $duplicates = $this->mergeService->findDuplicates($accountId, $criteria);

        // Load contact details for each group
        $groups = $duplicates->map(function ($group) {
            $contacts = Contact::with(['conversation', 'labels'])
                ->whereIn('id', $group['contact_ids'])
                ->get();

            return [
                'type' => $group['type'],
                'value' => $group['value'],
                'count' => $group['count'],
                'contacts' => $contacts->map(function ($contact) {
                    return [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'email' => $contact->email,
                        'phone_number' => $contact->phone_number,
                        'conversations_count' => $contact->conversations->count(),
                        'labels_count' => $contact->labels->count(),
                        'last_activity_at' => $contact->last_activity_at?->format('Y-m-d H:i:s'),
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'groups' => $groups,
            'total_groups' => $groups->count(),
        ]);
    }

    /**
     * Preview merge operation.
     */
    public function previewMerge(Request $request): JsonResponse
    {
        $request->validate([
            'primary_contact_id' => 'required|integer|exists:contacts,id',
            'duplicate_contact_ids' => 'required|array|min:1',
            'duplicate_contact_ids.*' => 'integer|exists:contacts,id',
        ]);

        $primaryContactId = $request->input('primary_contact_id');
        $duplicateContactIds = $request->input('duplicate_contact_ids');

        // Verify all contacts belong to current account
        $accountId = Auth::user()->account_id;
        $allContactIds = array_merge([$primaryContactId], $duplicateContactIds);

        $contactCount = Contact::whereIn('id', $allContactIds)
            ->where('account_id', $accountId)
            ->count();

        if ($contactCount !== count($allContactIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more contacts do not belong to your account.',
            ], 403);
        }

        $preview = $this->mergeService->previewMerge($primaryContactId, $duplicateContactIds);

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }

    /**
     * Execute merge operation.
     */
    public function executeMerge(Request $request): JsonResponse
    {
        $request->validate([
            'primary_contact_id' => 'required|integer|exists:contacts,id',
            'duplicate_contact_ids' => 'required|array|min:1',
            'duplicate_contact_ids.*' => 'integer|exists:contacts,id',
        ]);

        $primaryContactId = $request->input('primary_contact_id');
        $duplicateContactIds = $request->input('duplicate_contact_ids');

        // Verify all contacts belong to current account
        $accountId = Auth::user()->account_id;
        $allContactIds = array_merge([$primaryContactId], $duplicateContactIds);

        $contactCount = Contact::whereIn('id', $allContactIds)
            ->where('account_id', $accountId)
            ->count();

        if ($contactCount !== count($allContactIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more contacts do not belong to your account.',
            ], 403);
        }

        try {
            $mergedContact = $this->mergeService->mergeContacts($primaryContactId, $duplicateContactIds);

            return response()->json([
                'success' => true,
                'message' => 'Contacts merged successfully.',
                'contact' => [
                    'id' => $mergedContact->id,
                    'name' => $mergedContact->name,
                    'email' => $mergedContact->email,
                    'phone_number' => $mergedContact->phone_number,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to merge contacts: '.$e->getMessage(),
            ], 500);
        }
    }
}
