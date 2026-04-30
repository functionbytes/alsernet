<?php

namespace Modules\Chat\Http\Controllers\Settings\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Chat\Http\Controllers\Controller;

class ApiUsageDashboardController extends Controller
{
    /**
     * Show API usage dashboard
     */
    public function index(Request $request)
    {
        $date = now()->format('Y-m-d');
        $users = auth()->user()->account->users;

        // Get usage stats for each user
        $userStats = $users->map(function ($user) use ($date) {
            $key = "api:user:{$user->id}";

            return [
                'id' => $user->id,
                'name' => $user->name,
                'today' => Cache::get("api:usage:{$key}:{$date}", 0),
                'limit' => $this->getUserLimit($user),
            ];
        })->sortByDesc('today')->values();

        // Get top endpoints
        $endpoints = $this->getTopEndpoints($date);

        // Get hourly usage for chart
        $hourlyUsage = $this->getHourlyUsage($date);

        return view('Chat::chats.api-usage.index', compact('userStats', 'endpoints', 'hourlyUsage'));
    }

    /**
     * Get user rate limit
     */
    protected function getUserLimit($user): int
    {
        if ($user->role === 'administrator') {
            return 1000;
        }

        return 300;
    }

    /**
     * Get top endpoints by usage
     */
    protected function getTopEndpoints(string $date, int $limit = 10): array
    {
        $keys = Cache::get('api:endpoints:list', []);
        $endpoints = [];

        foreach ($keys as $endpoint) {
            $usage = Cache::get("api:endpoint:{$endpoint}:{$date}", 0);
            if ($usage > 0) {
                $endpoints[] = [
                    'endpoint' => $endpoint,
                    'requests' => $usage,
                ];
            }
        }

        usort($endpoints, fn ($a, $b) => $b['requests'] <=> $a['requests']);

        return array_slice($endpoints, 0, $limit);
    }

    /**
     * Get hourly usage for chart
     */
    protected function getHourlyUsage(string $date): array
    {
        $usage = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $hourFormatted = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $total = 0;

            // Sum all users for this hour
            $users = auth()->user()->account->users;
            foreach ($users as $user) {
                $key = "api:user:{$user->id}";
                $total += Cache::get("api:usage:{$key}:{$date}:{$hourFormatted}", 0);
            }

            $usage[] = [
                'hour' => $hourFormatted.':00',
                'requests' => $total,
            ];
        }

        return $usage;
    }

    /**
     * Show channels dashboard
     */
    public function add()
    {
        // Check which channels are configured
        $channels = [
            'whatsapp' => $this->isChannelConfigured('Whatsapp'),
            'facebook' => $this->isChannelConfigured('Facebook'),
            'instagram' => $this->isChannelConfigured('Instagram'),
            'sms' => $this->isChannelConfigured('Sms'),
        ];

        return view('Chat::settings.channels.add', compact('channels'));
    }

    /**
     * Check if a channel is configured
     */
    protected function isChannelConfigured(string $channelType): bool
    {
        $account = auth()->user()->account;
        $fullChannelType = "Modules\\Chat\\Models\\Channels\\{$channelType}";

        return $account->inboxes()
            ->where('channel_type', $fullChannelType)
            ->exists();
    }

    /**
     * Get API usage data (AJAX endpoint)
     */
    public function getData(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        return response()->json([
            'hourly_usage' => $this->getHourlyUsage($date),
            'top_endpoints' => $this->getTopEndpoints($date),
        ]);
    }
}
