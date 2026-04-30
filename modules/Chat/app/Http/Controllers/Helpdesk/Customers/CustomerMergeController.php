<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Services\Customers\CustomerMergeService;

class CustomerMergeController extends Controller
{
    public function __construct(
        protected CustomerMergeService $mergeService
    ) {}

    /**
     * Show the contact merge UI.
     */
    public function index(): View
    {
        return view('Chat::chats.customers.merge.index');
    }

    /**
     * Find duplicate customers.
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
            $customers = Customer::with(['conversations', 'labels'])
                ->whereIn('id', $group['contact_ids'])
                ->get();

            return [
                'type' => $group['type'],
                'value' => $group['value'],
                'count' => $group['count'],
                'contacts' => $customers->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone_number' => $customer->phone_number,
                        'conversations_count' => $customer->conversations->count(),
                        'labels_count' => $customer->labels->count(),
                        'last_activity_at' => $customer->last_activity_at?->diffForHumans(),
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
            'primary_contact_id' => 'required|integer|exists:chat_customers,id',
            'duplicate_contact_ids' => 'required|array|min:1',
            'duplicate_contact_ids.*' => 'integer|exists:chat_customers,id',
        ]);

        $primaryContactId = $request->input('primary_contact_id');
        $duplicateContactIds = $request->input('duplicate_contact_ids');

        // Verify all customers belong to current account
        $accountId = Auth::user()->account_id;
        $allContactIds = array_merge([$primaryContactId], $duplicateContactIds);

        $customerCount = Customer::whereIn('id', $allContactIds)
            ->where('account_id', $accountId)
            ->count();

        if ($customerCount !== count($allContactIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more customers do not belong to your account.',
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
            'primary_contact_id' => 'required|integer|exists:chat_customers,id',
            'duplicate_contact_ids' => 'required|array|min:1',
            'duplicate_contact_ids.*' => 'integer|exists:chat_customers,id',
        ]);

        $primaryContactId = $request->input('primary_contact_id');
        $duplicateContactIds = $request->input('duplicate_contact_ids');

        // Verify all customers belong to current account
        $accountId = Auth::user()->account_id;
        $allContactIds = array_merge([$primaryContactId], $duplicateContactIds);

        $customerCount = Customer::whereIn('id', $allContactIds)
            ->where('account_id', $accountId)
            ->count();

        if ($customerCount !== count($allContactIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more customers do not belong to your account.',
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
                'message' => 'Failed to merge customers: '.$e->getMessage(),
            ], 500);
        }
    }
}
