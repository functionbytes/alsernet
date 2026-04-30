<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Reports;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Analytics\CustomerReportService;

class CustomerReportController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $accountId = auth()->user()->account_id;
        abort_unless($accountId, 403, 'User must belong to an account');

        $dateRange = $this->getDateRange($request);
        $customerService = new CustomerReportService($accountId);
        $metrics = $customerService->getMetrics($dateRange['start'], $dateRange['end']);

        if ($request->wantsJson()) {
            return response()->json($metrics);
        }

        return view('Chat::chats.reports.customers.index', compact('metrics', 'dateRange'));
    }

    /**
     * Get date range from request or use defaults.
     */
    protected function getDateRange(Request $request): array
    {
        $start = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $end = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    public function export(Request $request): JsonResponse
    {
        $accountId = auth()->user()->account_id;
        abort_unless($accountId, 403, 'User must belong to an account');

        $dateRange = $this->getDateRange($request);
        $this->customerService = new CustomerReportService($accountId);
        $contacts = $this->customerService->exportData($dateRange['start'], $dateRange['end']);

        $filename = 'contact_report_'.now()->format('Y-m-d_His').'.csv';
        $filepath = 'exports/'.$filename;

        $csv = fopen('php://temp', 'w');

        fputcsv($csv, [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Company',
            'City',
            'Country',
            'Inboxes',
            'Conversations Count',
            'Created At',
            'Last Activity',
            'Blocked',
        ]);

        foreach ($contacts as $contact) {
            fputcsv($csv, [
                $contact['id'],
                $contact['name'],
                $contact['email'],
                $contact['phone'],
                $contact['company'],
                $contact['city'],
                $contact['country'],
                $contact['inboxes'],
                $contact['conversations_count'],
                $contact['created_at'],
                $contact['last_activity_at'],
                $contact['blocked'],
            ]);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        Storage::put($filepath, $csvContent);

        return response()->json([
            'message' => 'Report exported successfully',
            'download_url' => Storage::url($filepath),
            'filename' => $filename,
        ]);
    }
}
