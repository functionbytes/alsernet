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
        $totalSubscribers = Subscriber::count();
        $activeSubscribers = Subscriber::whereIn('status', ['active', 'subscribed'])->count();
        $validEmails = EmailValidation::valid()->count();
        $invalidEmails = EmailValidation::invalid()->count();
        $activeCampaigns = Campaign::where('status', 'sent')->count();
        $totalCampaigns = Campaign::count();

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
