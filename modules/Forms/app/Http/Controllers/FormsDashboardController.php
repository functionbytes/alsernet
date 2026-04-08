<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;

class FormsDashboardController extends Controller
{
    /**
     * Display the forms inbox dashboard with aggregated statistics.
     */
    public function index(Request $request): View
    {
        $range = $request->get('range', 'last_30_days');

        [$start, $end, $prevStart, $prevEnd, $days] = $this->resolveDates($range);

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $startPrevMonth = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $endPrevMonth = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $byStatus = FormSubmission::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $topForms = Form::query()
            ->select(
                'forms.id',
                'forms.name',
                DB::raw('count(form_submissions.id) as total'),
                DB::raw('sum(case when form_submissions.is_read = 0 and form_submissions.is_spam = 0 then 1 else 0 end) as unread'),
                DB::raw('sum(case when form_submissions.status = \'new\' then 1 else 0 end) as count_new'),
                DB::raw('sum(case when form_submissions.status = \'in_review\' then 1 else 0 end) as in_review'),
                DB::raw('sum(case when form_submissions.status = \'resolved\' then 1 else 0 end) as resolved'),
                DB::raw('sum(case when form_submissions.status = \'rejected\' then 1 else 0 end) as rejected'),
            )
            ->leftJoin('form_submissions', 'forms.id', '=', 'form_submissions.form_id')
            ->groupBy('forms.id', 'forms.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Sparkline: datos del período seleccionado por día
        $dailySubmissions = FormSubmission::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$start->toDateString(), $end->toDateString()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $sparkDays = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = $end->copy()->subDays($i)->toDateString();
            $sparkDays->push($dailySubmissions->get($d)?->total ?? 0);
        }

        $totalToday = FormSubmission::query()->whereDate('created_at', $today)->count();
        $totalMonth = FormSubmission::query()->whereDate('created_at', '>=', $startOfMonth)->count();
        $totalAll = FormSubmission::query()->count();
        $totalUnread = FormSubmission::query()->where('is_read', false)->where('is_spam', false)->count();

        $prevToday = FormSubmission::query()->whereDate('created_at', $yesterday)->count();
        $prevMonth = FormSubmission::query()->whereBetween(DB::raw('DATE(created_at)'), [$startPrevMonth, $endPrevMonth])->count();
        $prev30Days = FormSubmission::query()->whereBetween(DB::raw('DATE(created_at)'), [$prevStart->toDateString(), $prevEnd->toDateString()])->count();
        $curr30Days = FormSubmission::query()->whereBetween(DB::raw('DATE(created_at)'), [$start->toDateString(), $end->toDateString()])->count();

        return view('forms::inbox.dashboard', [
            'range' => $range,
            'totalToday' => $totalToday,
            'totalMonth' => $totalMonth,
            'totalAll' => $totalAll,
            'totalUnread' => $totalUnread,
            'byStatus' => array_merge(['new' => 0, 'in_review' => 0, 'resolved' => 0, 'rejected' => 0], $byStatus),
            'topForms' => $topForms,
            'dailySubmissions' => $dailySubmissions,
            'sparkDays' => $sparkDays->values()->toArray(),
            'prevToday' => $prevToday,
            'prevMonth' => $prevMonth,
            'prev30Days' => $prev30Days,
            'curr30Days' => $curr30Days,
        ]);
    }

    /**
     * @return array{Carbon, Carbon, Carbon, Carbon, int}
     */
    private function resolveDates(string $range): array
    {
        return match ($range) {
            'today' => [now()->startOfDay(),             now(),                             now()->subDay()->startOfDay(),             now()->subDay()->endOfDay(),             1],
            'last_7_days' => [now()->subDays(6)->startOfDay(), now(),                             now()->subDays(13)->startOfDay(),          now()->subDays(7)->endOfDay(),           7],
            'this_month' => [now()->startOfMonth(),           now(),                             now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth(), now()->day],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth(), now()->subMonths(2)->startOfMonth(), now()->subMonths(2)->endOfMonth(), now()->subMonthNoOverflow()->daysInMonth],
            'this_year' => [now()->startOfYear(),            now(),                             now()->subYear()->startOfYear(),           now()->subYear()->endOfYear(),           now()->dayOfYear],
            default => [now()->subDays(29)->startOfDay(), now(),                             now()->subDays(59)->startOfDay(),          now()->subDays(30)->endOfDay(),          30],
        };
    }
}
