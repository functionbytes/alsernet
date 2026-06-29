<?php

namespace Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Seo\Models\SeoAuditLog;
use Modules\Seo\Models\SeoMeta;

class SeoAuditHistoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Seo.metas.index');
    }

    public function index(): View
    {
        $search = request('search');
        $grade = request('grade');

        $logs = SeoAuditLog::query()
            ->with('seoMeta')
            ->when($search, fn ($q) => $q->where('url', 'like', "%{$search}%"))
            ->when($grade, fn ($q) => $q->where('grade', $grade))
            ->latest('audited_at')
            ->paginate(25)
            ->withQueryString();

        $row = SeoAuditLog::query()->selectRaw('
            COUNT(*) as total_audits,
            AVG(score) as avg_score,
            SUM(CASE WHEN grade = ? THEN 1 ELSE 0 END) as grade_a,
            SUM(CASE WHEN grade = ? THEN 1 ELSE 0 END) as grade_f
        ', ['A', 'F'])->first();

        $stats = [
            'total_audits' => (int) $row->total_audits,
            'avg_score' => round($row->avg_score ?? 0, 1),
            'grade_a' => (int) $row->grade_a,
            'grade_f' => (int) $row->grade_f,
        ];

        return view('Seo::settings.audit.history', compact('logs', 'stats'));
    }

    public function forMeta(SeoMeta $meta): View
    {
        $logs = SeoAuditLog::query()
            ->where('seo_meta_id', $meta->id)
            ->latest('audited_at')
            ->paginate(20);

        return view('Seo::settings.audit.meta-history', compact('logs', 'meta'));
    }

    /**
     * Return the last 10 audit logs for a meta as JSON (for inline history panel).
     */
    public function forMetaJson(SeoMeta $meta): JsonResponse
    {
        $logs = SeoAuditLog::forMeta($meta->id)
            ->orderByDesc('audited_at')
            ->limit(10)
            ->get(['id', 'score as seo_score', 'grade as seo_grade', 'issues', 'audited_at']);

        return response()->json($logs);
    }

    /**
     * Perform a bulk action on selected audit log entries.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'string', Rule::in(['delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:seo_audit_logs,id'],
        ]);

        $ids = $request->input('ids');

        $count = SeoAuditLog::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'count' => $count, 'message' => $count.' entrada(s) eliminadas.']);
    }

    /**
     * Clear all audit history logs.
     */
    public function clear(): RedirectResponse
    {
        SeoAuditLog::truncate();

        return back()->with('success', 'Historial de auditorías eliminado correctamente.');
    }

    /**
     * Delete a single audit log entry.
     */
    public function destroy(SeoAuditLog $log): RedirectResponse
    {
        $log->delete();

        return back()->with('success', 'Registro eliminado.');
    }
}
