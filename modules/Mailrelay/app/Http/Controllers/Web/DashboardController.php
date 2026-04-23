<?php

namespace Modules\Mailrelay\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\EmailValidation;
use Modules\Mailrelay\Entities\ImportJob;
use Modules\Mailrelay\Entities\Subscriber;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index()
    {
        // Get statistics
        $subscriberStats = Subscriber::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as active', ['active', 'subscribed'])
            ->first();

        $validationStats = EmailValidation::query()
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as valid', ['valid'])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as invalid', ['invalid'])
            ->first();

        $campaignStats = Campaign::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active', ['sent'])
            ->first();

        $totalSubscribers = (int) $subscriberStats->total;
        $activeSubscribers = (int) $subscriberStats->active;
        $validEmails = (int) $validationStats->valid;
        $invalidEmails = (int) $validationStats->invalid;
        $activeCampaigns = (int) $campaignStats->active;
        $totalCampaigns = (int) $campaignStats->total;

        // Get recent imports
        $recentImports = ImportJob::latest()->take(5)->get();

        // Get campaign metrics for chart
        $campaignMetrics = Campaign::with('analytics')
            ->where('status', 'sent')
            ->latest()
            ->take(5)
            ->get();

        // Recent subscribers
        $recentSubscribers = Subscriber::latest()->take(5)->get();

        return view('mailrelay::dashboard', compact(
            'totalSubscribers',
            'activeSubscribers',
            'validEmails',
            'invalidEmails',
            'activeCampaigns',
            'totalCampaigns',
            'recentImports',
            'campaignMetrics',
            'recentSubscribers'
        ));
    }
}
