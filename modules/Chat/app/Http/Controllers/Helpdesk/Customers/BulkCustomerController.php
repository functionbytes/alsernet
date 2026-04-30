<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Customers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Http\Requests\Customer\BulkAddLabelRequest;
use Modules\Chat\Http\Requests\Customer\BulkDeleteRequest;
use Modules\Chat\Http\Requests\Customer\BulkRemoveLabelRequest;
use Modules\Chat\Jobs\Customers\BulkAddLabelToCustomers;
use Modules\Chat\Jobs\Customers\BulkDeleteCustomers;
use Modules\Chat\Jobs\Customers\BulkRemoveLabelFromCustomers;
use Modules\Chat\Models\Customers\Customer;

class BulkCustomerController extends Controller
{
    /**
     * Add labels to multiple contacts.
     */
    public function addLabels(BulkAddLabelRequest $request)
    {
        $validated = $request->validated();
        $accountId = auth()->user()->account_id;

        $customerIds = Customer::whereIn('id', $validated['customer_ids'])
            ->where('account_id', $accountId)
            ->pluck('id')
            ->toArray();

        if (count($customerIds) !== count($validated['customer_ids'])) {
            return response()->json([
                'error' => 'Some contacts do not belong to your account',
            ], 403);
        }

        BulkAddLabelToCustomers::dispatch($accountId, $customerIds, $validated['label_ids']);

        return response()->json([
            'message' => 'Labels are being added to selected contacts',
            'count' => count($customerIds),
        ]);
    }

    /**
     * Remove labels from multiple contacts.
     */
    public function removeLabels(BulkRemoveLabelRequest $request)
    {
        $validated = $request->validated();
        $accountId = auth()->user()->account_id;

        $customerIds = Customer::whereIn('id', $validated['customer_ids'])
            ->where('account_id', $accountId)
            ->pluck('id')
            ->toArray();

        if (count($customerIds) !== count($validated['customer_ids'])) {
            return response()->json([
                'error' => 'Some contacts do not belong to your account',
            ], 403);
        }

        BulkRemoveLabelFromCustomers::dispatch($accountId, $customerIds, $validated['label_ids']);

        return response()->json([
            'message' => 'Labels are being removed from selected contacts',
            'count' => count($customerIds),
        ]);
    }

    /**
     * Delete multiple contacts.
     */
    public function delete(BulkDeleteRequest $request)
    {
        $validated = $request->validated();

        // Verify all contacts belong to user's account
        $customers = Customer::whereIn('id', $validated['customer_ids'])
            ->where('account_id', auth()->user()->account_id)
            ->pluck('id')
            ->toArray();

        if (count($customers) !== count($validated['customer_ids'])) {
            return response()->json([
                'error' => 'Some contacts do not belong to your account',
            ], 403);
        }

        BulkDeleteCustomers::dispatch(
            auth()->user()->account_id,
            $customers
        );

        return response()->json([
            'message' => 'Contacts are being deleted',
            'count' => count($customers),
        ]);
    }

    /**
     * Export multiple contacts to CSV.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:chat_customers,id',
        ]);

        $customers = Customer::whereIn('id', $validated['customer_ids'])
            ->where('account_id', auth()->user()->account_id)
            ->with(['customerInboxes.inbox'])
            ->get();

        // Generate CSV
        $filename = 'customers_export_'.now()->format('Y-m-d_His').'.csv';
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
        foreach ($customers as $customer) {
            fputcsv($csv, [
                $customer->id,
                $customer->full_name,
                $customer->email,
                $customer->phone_number,
                $customer->company_name,
                $customer->city,
                $customer->country,
                $customer->created_at->format('Y-m-d H:i:s'),
                $customer->last_activity_at?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        Storage::put($filepath, $csvContent);

        return response()->json([
            'message' => 'Export completed',
            'download_url' => Storage::url($filepath),
            'count' => count($customers),
        ]);
    }
}
