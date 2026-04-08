<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Reviews\Http\Requests\StoreReplyRequest;
use Modules\Reviews\Http\Requests\UpdateReplyRequest;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Services\ReviewReplyService;

class ReviewReplyController extends Controller
{
    public function __construct(
        private readonly ReviewReplyService $replyService
    ) {
        $this->authorizeResource(ReviewReply::class, 'reply');
    }

    public function scheduled(Request $request): View
    {
        $baseQuery = ReviewReply::query()
            ->whereHas('review', fn ($q) => $q->whereHas('location', fn ($lq) => $lq->whereHas('connection', fn ($cq) => $cq->where('user_id', auth()->id()))))
            ->whereNotNull('scheduled_at')
            ->where('status', 'scheduled');

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'next_24h' => (clone $baseQuery)->where('scheduled_at', '<=', now()->addDay())->count(),
            'this_week' => (clone $baseQuery)->where('scheduled_at', '<=', now()->addWeek())->count(),
            'overdue' => (clone $baseQuery)->where('scheduled_at', '<', now())->count(),
        ];

        $replies = (clone $baseQuery)
            ->when($request->search, fn ($q) => $q->where('reply_text', 'like', '%'.$request->search.'%'))
            ->with(['review'])
            ->orderBy('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return view('reviews::replies.scheduled', compact('replies', 'stats'));
    }

    public function bulkCancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = ReviewReply::query()
            ->whereIn('id', $validated['ids'])
            ->whereHas('review', fn ($q) => $q->whereHas('location', fn ($lq) => $lq->whereHas('connection', fn ($cq) => $cq->where('user_id', auth()->id()))))
            ->whereNotNull('scheduled_at')
            ->where('status', 'scheduled')
            ->delete();

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function store(StoreReplyRequest $request): JsonResponse
    {
        try {
            $review = Review::query()->findOrFail($request->validated('review_id'));

            $reply = $this->replyService->createDraft(
                $review,
                $request->validated('reply_text'),
                auth()->user()
            );

            $status = $request->input('status', 'draft');

            if ($status === 'published' && auth()->user()->can('reviews.replies.publish')) {
                $reply = $this->replyService->publish($reply, auth()->user());
                $message = 'Respuesta publicada en Google correctamente';
            } elseif ($status === 'approved' && auth()->user()->can('reviews.replies.approve')) {
                $reply = $this->replyService->approve($reply, auth()->user());
                $message = 'Respuesta aprobada correctamente';
            } else {
                $message = 'Respuesta guardada como borrador';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'reply' => $reply->load(['createdBy', 'approvedBy']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing review reply', [
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al guardar la respuesta. Por favor intenta más tarde.',
            ], 422);
        }
    }

    public function update(UpdateReplyRequest $request, ReviewReply $reply): JsonResponse
    {
        try {
            $reply = $this->replyService->updateDraft(
                $reply,
                $request->validated('reply_text'),
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Respuesta actualizada correctamente',
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating review reply', [
                'reply_id' => $reply->id,
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al actualizar la respuesta. Por favor intenta más tarde.',
            ], 422);
        }
    }

    public function publish(ReviewReply $reply): JsonResponse
    {
        $this->authorize('publish', $reply);

        try {
            $reply = $this->replyService->publish($reply, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Respuesta publicada en Google correctamente',
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error('Error publishing review reply', [
                'reply_id' => $reply->id,
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al publicar la respuesta. Por favor intenta más tarde.',
            ], 422);
        }
    }

    public function schedule(Request $request, ReviewReply $reply): JsonResponse
    {
        $this->authorize('approve', $reply);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        try {
            $reply = $this->replyService->schedule(
                $reply,
                Carbon::parse($validated['scheduled_at']),
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Respuesta programada correctamente',
                'reply' => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error('Error scheduling review reply', [
                'reply_id' => $reply->id,
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al programar la respuesta. Por favor intenta más tarde.',
            ], 422);
        }
    }

    public function destroy(ReviewReply $reply): JsonResponse
    {
        try {
            $this->replyService->delete($reply, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Respuesta eliminada correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting review reply', [
                'reply_id' => $reply->id,
                'code' => $e->getCode(),
                'file' => class_basename($e->getFile()),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ha ocurrido un error al eliminar la respuesta. Por favor intenta más tarde.',
            ], 422);
        }
    }
}
