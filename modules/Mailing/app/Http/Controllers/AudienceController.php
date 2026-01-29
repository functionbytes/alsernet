<?php

namespace Modules\Mailing\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AudienceController extends Controller
{
    public function overview(Request $request)
    {
        // stats
        $subscriberCount = $request->user()->customer->subscribers()->count();
        $subscribedCount = $request->user()->customer->subscribers()->subscribed()->count();
        $activeContactPercent = $subscriberCount > 0 ? ($subscribedCount / $subscriberCount) : 0;

        return view('audience.overview', [
            'subscriberCount' => $subscriberCount,
            'subscribedCount' => $subscribedCount,
            'activeContactPercent' => $activeContactPercent,
            'formCount' => $request->user()->customer->forms()->count(),
            'blacklistedCount' => $request->user()->customer->subscribers()
                ->join('blacklists', 'subscribers.email', '=', 'blacklists.email')->count(),
        ]);
    }

    public function growthChart(Request $request)
    {
        $currentTimezone = $request->user()->customer->getTimezone();

        $result = [
            'columns' => [],
            'total' => [],
            'unsubscribed' => [],
        ];

        $times = [];

        // columns
        for ($i = 15; $i >= 0; $i--) {
            $time = Carbon::now($currentTimezone)->subMonths($i);
            $result['columns'][] = $time->format('y M');
            $times[] = $time;
        }

        // data
        foreach ($times as $time) {
            $result['total'][] = \Modules\Mailing\Models\Customer::subscribersCountByTime(
                \Carbon\Carbon::now($currentTimezone)->subYears(1000),
                $time->endOfDay(),
                $request->user()->customer->id
            );

            $result['unsubscribed'][] = \Modules\Mailing\Models\Customer::subscribersCountByTime(
                \Carbon\Carbon::now($currentTimezone)->subYears(1000),
                $time->endOfDay(),
                $request->user()->customer->id,
                null,
                \Modules\Mailing\Models\Subscriber::STATUS_UNSUBSCRIBED
            );
        }

        return response()->json($result);
    }
}
