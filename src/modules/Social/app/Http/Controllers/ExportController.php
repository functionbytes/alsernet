<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Social\Exports\PostsExport;
use Modules\Social\Models\Post;

class ExportController extends Controller
{
    public function exportPostsExcel(Request $request)
    {
        $filters = $request->only(['status', 'date_from', 'date_to', 'campaign_id']);

        return Excel::download(
            new PostsExport(auth()->user()->account_id, $filters),
            'posts-'.date('Y-m-d').'.xlsx'
        );
    }

    public function exportPostsPDF(Request $request)
    {
        $query = Post::where('account_id', auth()->user()->account_id)
            ->with(['socialAccount', 'campaign', 'creator']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $posts = $query->orderBy('created_at', 'desc')->get();

        $pdf = Pdf::loadView('social::exports.posts-pdf', [
            'posts' => $posts,
            'generated_at' => now()->format('Y-m-d H:i'),
        ]);

        return $pdf->download('posts-'.date('Y-m-d').'.pdf');
    }

    public function exportAnalyticsPDF(Request $request)
    {
        $accountId = auth()->user()->account_id;

        // Get analytics data (similar to AnalyticsController)
        $totalPosts = Post::where('account_id', $accountId)->count();
        $publishedPosts = Post::where('account_id', $accountId)->where('status', 'published')->count();

        $totalEngagement = Post::where('account_id', $accountId)
            ->selectRaw('SUM(likes_count + comments_count + shares_count) as engagement_total')
            ->value('engagement_total') ?? 0;

        $topPosts = Post::where('account_id', $accountId)
            ->where('status', 'published')
            ->orderByRaw('(likes_count + comments_count + shares_count) DESC')
            ->limit(10)
            ->get();

        $pdf = Pdf::loadView('social::exports.analytics-pdf', [
            'totalPosts' => $totalPosts,
            'publishedPosts' => $publishedPosts,
            'totalEngagement' => $totalEngagement,
            'topPosts' => $topPosts,
            'generated_at' => now()->format('Y-m-d H:i'),
        ]);

        return $pdf->download('analytics-report-'.date('Y-m-d').'.pdf');
    }
}
