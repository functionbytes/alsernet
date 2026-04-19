<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Forms\Models\Form;
use Modules\Page\Models\Page;

class FormAnalyticsService
{
    /**
     * @return array{
     *   submissions_by_day: Collection,
     *   submissions_by_status: Collection,
     *   submissions_by_hour: Collection,
     *   total_submissions: int,
     *   abandon_count: int,
     *   conversion_rate: float
     * }
     */
    public function overview(Form $form, int $daysBack = 30): array
    {
        $submissionsByDay = DB::table('form_submissions')
            ->where('form_id', $form->id)
            ->where('created_at', '>=', now()->subDays($daysBack))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $submissionsByStatus = DB::table('form_submissions')
            ->where('form_id', $form->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $submissionsByHour = DB::table('form_submissions')
            ->where('form_id', $form->id)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $totalSubmissions = $form->submissions()->count();
        $abandonCount = DB::table('form_abandon_tracking')
            ->where('form_id', $form->id)
            ->count();

        $conversionRate = ($totalSubmissions + $abandonCount) > 0
            ? round(($totalSubmissions / ($totalSubmissions + $abandonCount)) * 100, 1)
            : 0.0;

        return [
            'submissions_by_day' => $submissionsByDay,
            'submissions_by_status' => $submissionsByStatus,
            'submissions_by_hour' => $submissionsByHour,
            'total_submissions' => $totalSubmissions,
            'abandon_count' => $abandonCount,
            'conversion_rate' => (float) $conversionRate,
            'top_source_pages' => $this->topSourcePages($form),
        ];
    }

    /**
     * Top Pages que generan más submissions para este form.
     */
    public function topSourcePages(Form $form, int $limit = 10): Collection
    {
        if (! class_exists(Page::class)) {
            return collect();
        }

        return DB::table('form_submissions')
            ->join('pages', 'pages.id', '=', 'form_submissions.source_page_id')
            ->select('pages.id', 'pages.title', 'pages.slug', DB::raw('COUNT(*) as submissions_count'))
            ->where('form_submissions.form_id', $form->id)
            ->whereNotNull('form_submissions.source_page_id')
            ->groupBy('pages.id', 'pages.title', 'pages.slug')
            ->orderByDesc('submissions_count')
            ->limit($limit)
            ->get();
    }
}
