<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Review;

class ReviewReplyController extends Controller
{
    public function store(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if ($review->reply) {
            return response()->json(['message' => 'Ya se respondio esta resena.'], 422);
        }

        $review->update([
            'reply' => $validated['message'],
            'replied_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta guardada.',
            'reply' => $validated['message'],
            'replied_at' => now()->format('d/m/Y H:i'),
        ]);
    }

    public function update(Request $request, Review $review): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $review->update([
            'reply' => $validated['message'],
            'replied_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuesta guardada.',
            'reply' => $validated['message'],
            'replied_at' => now()->format('d/m/Y H:i'),
        ]);
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->update([
            'reply' => null,
            'replied_at' => null,
        ]);

        return response()->json(['success' => true]);
    }
}
