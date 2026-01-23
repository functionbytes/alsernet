<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactInbox;

class ContactReportController extends Controller
{
    /**
     * Display contact reports dashboard.
     */
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;

        $dateRange = $this->getDateRange($request);

        $metrics = [
            'total_contacts' => $this->getTotalContacts($accountId),
            'new_contacts' => $this->getNewContacts($accountId, $dateRange),
            'active_contacts' => $this->getActiveContacts($accountId, $dateRange),
            'blocked_contacts' => $this->getBlockedContacts($accountId),
            'contacts_by_inbox' => $this->getContactsByInbox($accountId),
            'contacts_by_country' => $this->getContactsByCountry($accountId),
            'growth_trend' => $this->getGrowthTrend($accountId, $dateRange),
            'activity_trend' => $this->getActivityTrend($accountId, $dateRange),
        ];

        if ($request->wantsJson()) {
            return response()->json($metrics);
        }

        return view('helpdeskchat::admin.reports.contacts.index', compact('metrics', 'dateRange'));
    }

    /**
     * Get total contacts count.
     */
    protected function getTotalContacts(int $accountId): int
    {
        return Contact::where('account_id', $accountId)->count();
    }

    /**
     * Get new contacts in date range.
     */
    protected function getNewContacts(int $accountId, array $dateRange): int
    {
        return Contact::where('account_id', $accountId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();
    }

    /**
     * Get active contacts (with recent activity).
     */
    protected function getActiveContacts(int $accountId, array $dateRange): int
    {
        return Contact::where('account_id', $accountId)
            ->whereBetween('last_activity_at', [$dateRange['start'], $dateRange['end']])
            ->count();
    }

    /**
     * Get blocked contacts count.
     */
    protected function getBlockedContacts(int $accountId): int
    {
        return Contact::where('account_id', $accountId)
            ->where('blocked', true)
            ->count();
    }

    /**
     * Get contacts distribution by inbox.
     */
    protected function getContactsByInbox(int $accountId): array
    {
        return ContactInbox::select('inboxes.name', DB::raw('COUNT(DISTINCT contact_inboxes.contact_id) as count'))
            ->join('inboxes', 'contact_inboxes.inbox_id', '=', 'inboxes.id')
            ->where('inboxes.account_id', $accountId)
            ->groupBy('inboxes.id', 'inboxes.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->name,
                'value' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get contacts distribution by country.
     */
    protected function getContactsByCountry(int $accountId): array
    {
        return Contact::select('country', DB::raw('COUNT(*) as count'))
            ->where('account_id', $accountId)
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'label' => $item->country ?: 'Unknown',
                'value' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get contact growth trend over time.
     */
    protected function getGrowthTrend(int $accountId, array $dateRange): array
    {
        $days = (int) $dateRange['start']->diffInDays($dateRange['end']);
        $groupBy = $days > 90 ? 'month' : 'day';

        $query = Contact::select(
            DB::raw("DATE({$this->getDateFormat($groupBy)}) as period"),
            DB::raw('COUNT(*) as count')
        )
            ->where('account_id', $accountId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('period')
            ->orderBy('period');

        return $query->get()
            ->map(fn ($item) => [
                'date' => $item->period,
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get activity trend over time.
     */
    protected function getActivityTrend(int $accountId, array $dateRange): array
    {
        $days = (int) $dateRange['start']->diffInDays($dateRange['end']);
        $groupBy = $days > 90 ? 'month' : 'day';

        $query = Contact::select(
            DB::raw("DATE({$this->getDateFormat($groupBy)}) as period"),
            DB::raw('COUNT(DISTINCT id) as count')
        )
            ->where('account_id', $accountId)
            ->whereNotNull('last_activity_at')
            ->whereBetween('last_activity_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('period')
            ->orderBy('period');

        return $query->get()
            ->map(fn ($item) => [
                'date' => $item->period,
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get date format based on grouping.
     */
    protected function getDateFormat(string $groupBy): string
    {
        return match ($groupBy) {
            'month' => "DATE_FORMAT(created_at, '%Y-%m-01')",
            'week' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
            default => 'DATE(created_at)',
        };
    }

    /**
     * Get date range from request or use defaults.
     */
    protected function getDateRange(Request $request): array
    {
        $start = $request->input('start_date')
            ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $end = $request->input('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Export contacts report data.
     */
    public function export(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $dateRange = $this->getDateRange($request);

        $contacts = Contact::where('account_id', $accountId)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->with(['contactInboxes.inbox'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'contact_report_'.now()->format('Y-m-d_His').'.csv';
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
            'Inboxes',
            'Conversations Count',
            'Created At',
            'Last Activity',
            'Blocked',
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
                $contact->contactInboxes->pluck('inbox.name')->implode(', '),
                $contact->conversations->count(),
                $contact->created_at->format('Y-m-d H:i:s'),
                $contact->last_activity_at?->format('Y-m-d H:i:s'),
                $contact->blocked ? 'Yes' : 'No',
            ]);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        \Illuminate\Support\Facades\Storage::put($filepath, $csvContent);

        return response()->json([
            'message' => 'Report exported successfully',
            'download_url' => \Illuminate\Support\Facades\Storage::url($filepath),
            'filename' => $filename,
        ]);
    }
}
