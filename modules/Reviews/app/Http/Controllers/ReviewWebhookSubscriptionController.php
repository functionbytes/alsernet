<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Models\ReviewWebhookSubscription;

class ReviewWebhookSubscriptionController extends Controller
{
    /**
     * Supported webhook event types.
     */
    private const SUPPORTED_EVENTS = ['review.created', 'reply.published', 'review.anomaly'];

    public function index(Request $request): View
    {
        $baseQuery = ReviewWebhookSubscription::query()
            ->where('user_id', auth()->id());

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            'failed' => (clone $baseQuery)->where('failure_count', '>', 0)->count(),
        ];

        $query = clone $baseQuery;

        if ($search = $request->input('search')) {
            $query->where('url', 'like', "%{$search}%");
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $subscriptions = $query->latest()->paginate(20)->withQueryString();

        return view('reviews::webhooks.index', compact('subscriptions', 'stats'));
    }

    public function toggle(ReviewWebhookSubscription $webhook): JsonResponse
    {
        abort_if($webhook->user_id !== auth()->id(), 403);

        $webhook->update(['is_active' => ! $webhook->is_active]);

        return response()->json([
            'message' => $webhook->is_active ? 'Suscripcion activada.' : 'Suscripcion desactivada.',
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $query = ReviewWebhookSubscription::whereIn('id', $request->input('ids'))
            ->where('user_id', auth()->id());

        $count = match ($request->input('action')) {
            'activate' => $query->update(['is_active' => true]),
            'deactivate' => $query->update(['is_active' => false]),
            'delete' => tap($query->count(), fn () => $query->delete()),
        };

        return response()->json(['count' => $count]);
    }

    public function create(): View
    {
        return view('reviews::webhooks.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', self::SUPPORTED_EVENTS)],
            'secret' => ['sometimes', 'nullable', 'string', 'max:64'],
            'is_active' => ['boolean'],
        ]);

        ReviewWebhookSubscription::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('reviews.webhook-subscriptions.index')
            ->with('success', 'Suscripcion de webhook creada correctamente.');
    }

    public function edit(ReviewWebhookSubscription $webhook): View
    {
        abort_if($webhook->user_id !== auth()->id(), 403);

        return view('reviews::webhooks.edit', compact('webhook'));
    }

    public function update(Request $request, ReviewWebhookSubscription $webhook): RedirectResponse
    {
        abort_if($webhook->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', self::SUPPORTED_EVENTS)],
            'is_active' => ['boolean'],
        ]);

        $webhook->update($validated);

        return redirect()->route('reviews.webhook-subscriptions.index')
            ->with('success', 'Suscripcion de webhook actualizada correctamente.');
    }

    public function destroy(ReviewWebhookSubscription $webhook): JsonResponse
    {
        abort_if($webhook->user_id !== auth()->id(), 403);

        $webhook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Suscripcion de webhook eliminada correctamente.',
        ]);
    }
}
