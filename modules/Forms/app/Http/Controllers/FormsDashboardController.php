<?php

namespace Modules\Forms\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;

class FormsDashboardController extends Controller
{
    /**
     * Display the forms inbox dashboard with aggregated statistics.
     */
    public function index(): \Illuminate\View\View
    {
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $thirtyDaysAgo = now()->subDays(29)->startOfDay();

        $byStatus = FormSubmission::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusCounts = array_merge(
            ['new' => 0, 'in_review' => 0, 'resolved' => 0, 'rejected' => 0],
            $byStatus
        );

        $topForms = Form::query()
            ->select('forms.id', 'forms.name', DB::raw('count(form_submissions.id) as submissions_count'))
            ->leftJoin('form_submissions', 'forms.id', '=', 'form_submissions.form_id')
            ->groupBy('forms.id', 'forms.name')
            ->orderByDesc('submissions_count')
            ->limit(5)
            ->get();

        $dailySubmissions = FormSubmission::query()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        return view('forms::inbox.dashboard', [
            'totalToday' => FormSubmission::query()->whereDate('created_at', $today)->count(),
            'totalMonth' => FormSubmission::query()->whereDate('created_at', '>=', $startOfMonth)->count(),
            'totalAll' => FormSubmission::query()->count(),
            'unreadTotal' => FormSubmission::query()->where('is_read', false)->where('is_spam', false)->count(),
            'byStatus' => $statusCounts,
            'topForms' => $topForms,
            'dailySubmissions' => $dailySubmissions,
        ]);
    }
}
