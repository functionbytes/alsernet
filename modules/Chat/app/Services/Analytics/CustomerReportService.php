<?php

namespace Modules\Chat\Services\Analytics;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerInbox;

class CustomerReportService
{
    public function __construct(
        protected int $accountId
    ) {}

    /**
     * Get all customer metrics for a date range.
     */
    public function getMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_contacts' => $this->getTotalContacts(),
            'new_contacts' => $this->getNewContacts($startDate, $endDate),
            'active_contacts' => $this->getActiveContacts($startDate, $endDate),
            'blocked_contacts' => $this->getBlockedContacts(),
            'contacts_by_inbox' => $this->getContactsByInbox(),
            'contacts_by_country' => $this->getContactsByCountry(),
            'growth_trend' => $this->getGrowthTrend($startDate, $endDate),
            'activity_trend' => $this->getActivityTrend($startDate, $endDate),
        ];
    }

    /**
     * Get total contacts count.
     */
    public function getTotalContacts(): int
    {
        return Customer::where('account_id', $this->accountId)->count();
    }

    /**
     * Get new contacts in date range.
     */
    public function getNewContacts(Carbon $startDate, Carbon $endDate): int
    {
        return Customer::where('account_id', $this->accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get active contacts (with recent activity).
     */
    public function getActiveContacts(Carbon $startDate, Carbon $endDate): int
    {
        return Customer::where('account_id', $this->accountId)
            ->whereBetween('last_activity_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get blocked contacts count.
     */
    public function getBlockedContacts(): int
    {
        return Customer::where('account_id', $this->accountId)
            ->where('blocked', true)
            ->count();
    }

    /**
     * Get contacts distribution by inbox.
     */
    public function getContactsByInbox(): array
    {
        return CustomerInbox::select('inboxes.name', DB::raw('COUNT(DISTINCT chat_customer_inboxes.customer_id) as count'))
            ->join('inboxes', 'chat_customer_inboxes.inbox_id', '=', 'inboxes.id')
            ->where('inboxes.account_id', $this->accountId)
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
    public function getContactsByCountry(int $limit = 10): array
    {
        return Customer::select('country', DB::raw('COUNT(*) as count'))
            ->where('account_id', $this->accountId)
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit($limit)
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
    public function getGrowthTrend(Carbon $startDate, Carbon $endDate): array
    {
        $days = (int) $startDate->diffInDays($endDate);
        $groupBy = $days > 90 ? 'month' : 'day';

        $query = Customer::select(
            DB::raw("DATE({$this->getDateFormat($groupBy)}) as period"),
            DB::raw('COUNT(*) as count')
        )
            ->where('account_id', $this->accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
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
    public function getActivityTrend(Carbon $startDate, Carbon $endDate): array
    {
        $days = (int) $startDate->diffInDays($endDate);
        $groupBy = $days > 90 ? 'month' : 'day';

        $query = Customer::select(
            DB::raw("DATE({$this->getDateFormat($groupBy)}) as period"),
            DB::raw('COUNT(DISTINCT id) as count')
        )
            ->where('account_id', $this->accountId)
            ->whereNotNull('last_activity_at')
            ->whereBetween('last_activity_at', [$startDate, $endDate])
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
     * Export contacts data for a date range.
     */
    public function exportData(Carbon $startDate, Carbon $endDate): array
    {
        return Customer::where('account_id', $this->accountId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['customerInboxes.inbox'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($contact) => [
                'id' => $contact->id,
                'name' => $contact->full_name,
                'email' => $contact->email,
                'phone' => $contact->phone_number,
                'company' => $contact->company_name,
                'city' => $contact->city,
                'country' => $contact->country,
                'inboxes' => $contact->customerInboxes->pluck('inbox.name')->implode(', '),
                'conversations_count' => $contact->conversations->count(),
                'created_at' => $contact->created_at->format('Y-m-d H:i:s'),
                'last_activity_at' => $contact->last_activity_at?->format('Y-m-d H:i:s'),
                'blocked' => $contact->blocked ? 'Yes' : 'No',
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
}
