<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Social\Models\SocialListeningKeyword;
use Modules\Social\Services\SocialListeningService;

class SocialListeningController extends Controller
{
    public function __construct(
        protected SocialListeningService $listeningService
    ) {}

    /**
     * Show social listening dashboard
     */
    public function index(Request $request): View
    {
        $accountId = auth()->user()->account_id;

        // Get all keywords
        $keywords = SocialListeningKeyword::where('account_id', $accountId)
            ->with('creator')
            ->latest()
            ->get();

        // Get stats
        $stats = $this->listeningService->getListeningStats($accountId);

        return view('social::listening.index', compact('keywords', 'stats'));
    }

    /**
     * Show create keyword form
     */
    public function create(): View
    {
        return view('social::listening.create');
    }

    /**
     * Store new keyword
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:keyword,hashtag,mention,competitor',
            'networks' => 'required|array|min:1',
            'networks.*' => 'in:facebook,instagram,twitter,linkedin',
            'sentiment_filter' => 'required|in:all,positive,negative,neutral',
            'notify_on_match' => 'boolean',
            'color' => 'nullable|string|max:7',
            'notes' => 'nullable|string|max:500',
        ]);

        $keyword = SocialListeningKeyword::create([
            ...$validated,
            'account_id' => auth()->user()->account_id,
            'created_by' => auth()->id(),
            'is_active' => true,
            'notify_on_match' => $request->boolean('notify_on_match'),
        ]);

        return redirect()->route('admin.social.listening.index')
            ->with('success', 'Keyword creada exitosamente');
    }

    /**
     * Show edit keyword form
     */
    public function edit(SocialListeningKeyword $keyword): View
    {
        // Verify ownership
        if ($keyword->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        return view('social::listening.edit', compact('keyword'));
    }

    /**
     * Update keyword
     */
    public function update(Request $request, SocialListeningKeyword $keyword): RedirectResponse
    {
        // Verify ownership
        if ($keyword->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:keyword,hashtag,mention,competitor',
            'networks' => 'required|array|min:1',
            'networks.*' => 'in:facebook,instagram,twitter,linkedin',
            'sentiment_filter' => 'required|in:all,positive,negative,neutral',
            'notify_on_match' => 'boolean',
            'color' => 'nullable|string|max:7',
            'notes' => 'nullable|string|max:500',
        ]);

        $keyword->update([
            ...$validated,
            'notify_on_match' => $request->boolean('notify_on_match'),
        ]);

        return redirect()->route('admin.social.listening.index')
            ->with('success', 'Keyword actualizada exitosamente');
    }

    /**
     * Delete keyword
     */
    public function destroy(SocialListeningKeyword $keyword): RedirectResponse
    {
        // Verify ownership
        if ($keyword->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $keyword->delete();

        return redirect()->route('admin.social.listening.index')
            ->with('success', 'Keyword eliminada exitosamente');
    }

    /**
     * Toggle keyword active status
     */
    public function toggle(SocialListeningKeyword $keyword): JsonResponse
    {
        // Verify ownership
        if ($keyword->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $keyword->toggleActive();

        return response()->json([
            'success' => true,
            'is_active' => $keyword->is_active,
            'message' => $keyword->is_active ? 'Keyword activada' : 'Keyword pausada',
        ]);
    }

    /**
     * Manually scan a keyword
     */
    public function scan(SocialListeningKeyword $keyword): JsonResponse
    {
        // Verify ownership
        if ($keyword->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        try {
            $results = $this->listeningService->scanKeyword($keyword);

            return response()->json([
                'success' => true,
                'message' => "Escaneo completado. {$results['mentions_found']} menciones encontradas.",
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al escanear: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show mentions for a keyword
     */
    public function mentions(Request $request, SocialListeningKeyword $keyword): View
    {
        // Verify ownership
        if ($keyword->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $mentions = $keyword->mentions()
            ->with(['socialAccount'])
            ->latest()
            ->paginate(20);

        $sentimentStats = $keyword->getSentimentDistribution();

        return view('social::listening.mentions', compact('keyword', 'mentions', 'sentimentStats'));
    }

    /**
     * Scan all keywords (AJAX)
     */
    public function scanAll(): JsonResponse
    {
        $accountId = auth()->user()->account_id;

        try {
            $results = $this->listeningService->scanAllKeywords($accountId);

            return response()->json([
                'success' => true,
                'message' => "Escaneo completado. {$results['total_mentions']} menciones encontradas en {$results['keywords_scanned']} keywords.",
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al escanear: '.$e->getMessage(),
            ], 500);
        }
    }
}
