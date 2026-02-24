<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Reviews\Http\Requests\StoreReplyRequest;
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(StoreReplyRequest $request, ReviewReply $reply): JsonResponse
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
