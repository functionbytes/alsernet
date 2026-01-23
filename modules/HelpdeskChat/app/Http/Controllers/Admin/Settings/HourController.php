<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Settings;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Hour;
use Modules\HelpdeskChat\Services\HourService;

class HourController extends Controller
{
    public function __construct(
        protected HourService $businessHourService
    ) {}

    /**
     * Display business hours settings.
     */
    public function index(Request $request)
    {
        $accountId = $request->user()->account_id;

        // Get business hours grouped by day
        $businessHours = Hour::forAccount($accountId)
            ->whereNull('inbox_id')
            ->orderBy('day_of_week')
            ->get()
            ->groupBy('day_of_week');

        // If no business hours exist, create defaults
        if ($businessHours->isEmpty()) {
            $this->businessHourService->createDefaultBusinessHours(
                $request->user()->account,
                null,
                config('app.timezone')
            );

            $businessHours = Hour::forAccount($accountId)
                ->whereNull('inbox_id')
                ->orderBy('day_of_week')
                ->get()
                ->groupBy('day_of_week');
        }

        // Pass days of week to view
        $daysOfWeek = Hour::$daysOfWeek;

        return view('helpdeskchat::admin.settings.hours.index', compact('businessHours', 'daysOfWeek'));
    }

    /**
     * Update business hours.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'business_hours' => 'required|array',
            'business_hours.*.id' => 'required|exists:business_hours,id',
            'business_hours.*.is_enabled' => 'required|boolean',
            'business_hours.*.open_time' => 'required|date_format:H:i',
            'business_hours.*.close_time' => 'required|date_format:H:i|after:business_hours.*.open_time',
            'timezone' => 'required|string|timezone',
        ]);

        foreach ($validated['business_hours'] as $hourData) {
            $businessHour = Hour::find($hourData['id']);

            if ($businessHour && $businessHour->account_id === $request->user()->account_id) {
                $businessHour->update([
                    'is_enabled' => $hourData['is_enabled'],
                    'open_time' => $hourData['open_time'],
                    'close_time' => $hourData['close_time'],
                    'timezone' => $validated['timezone'],
                ]);
            }
        }

        return back()->with('success', 'Business hours updated successfully!');
    }

    /**
     * Reset to default business hours.
     */
    public function reset(Request $request)
    {
        $accountId = $request->user()->account_id;

        // Delete existing business hours
        Hour::forAccount($accountId)->whereNull('inbox_id')->delete();

        // Create default hours
        $this->businessHourService->createDefaultBusinessHours(
            $request->user()->account,
            null,
            config('app.timezone')
        );

        return back()->with('success', 'Business hours reset to default (Monday-Friday, 9am-5pm)');
    }
}
