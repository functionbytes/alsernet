<?php

namespace Modules\Chat\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Events\LowCsatRatingReceivedEvent;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Csat\CsatSurveyResponse;

class CsatSurveyController extends Controller
{
    /**
     * Get CSAT survey by token.
     *
     * GET /api/csat/survey/{token}
     */
    public function show(Request $request, string $token): JsonResponse
    {
        // Find survey by token
        $survey = CsatSurveyResponse::where('survey_token', $token)
            ->with(['conversation.customer', 'conversation.assignee'])
            ->first();

        if (! $survey) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not found or invalid token',
            ], 404);
        }

        // Check if already submitted
        if ($survey->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'This survey has already been submitted',
                'data' => [
                    'submitted' => true,
                    'submitted_at' => $survey->submitted_at->toIso8601String(),
                    'rating' => $survey->rating,
                    'rating_emoji' => $survey->rating_emoji,
                ],
            ], 422);
        }

        // Check if survey is expired (30 days old)
        if ($survey->created_at->addDays(30)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'This survey has expired',
            ], 410);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'conversation_id' => $survey->conversation_id,
                'customer_name' => $survey->conversation->customer->name ?? 'Customer',
                'assigned_agent_name' => $survey->conversation->assignee->name ?? null,
                'questions' => [
                    [
                        'id' => 'rating',
                        'type' => 'rating',
                        'question' => 'How satisfied are you with our service?',
                        'required' => true,
                        'scale' => 5,
                        'labels' => [
                            1 => 'Very Dissatisfied',
                            2 => 'Dissatisfied',
                            3 => 'Neutral',
                            4 => 'Satisfied',
                            5 => 'Very Satisfied',
                        ],
                    ],
                    [
                        'id' => 'feedback',
                        'type' => 'textarea',
                        'question' => 'Please share any additional feedback (optional)',
                        'required' => false,
                        'placeholder' => 'Tell us how we can improve...',
                    ],
                ],
                'submitted' => false,
                'expires_at' => $survey->created_at->addDays(30)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Submit CSAT survey response.
     *
     * POST /api/csat/survey/{token}/submit
     */
    public function submit(Request $request, string $token): JsonResponse
    {
        // Find survey by token
        $survey = CsatSurveyResponse::where('survey_token', $token)
            ->with('conversation')
            ->first();

        if (! $survey) {
            return response()->json([
                'success' => false,
                'message' => 'Survey not found or invalid token',
            ], 404);
        }

        // Check if already submitted
        if ($survey->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'This survey has already been submitted',
            ], 422);
        }

        // Check if survey is expired
        if ($survey->created_at->addDays(30)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'This survey has expired',
            ], 410);
        }

        // Validate request
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ]);

        // Update survey response
        $survey->update([
            'rating' => $validated['rating'],
            'feedback' => $validated['feedback'] ?? null,
            'submitted_at' => now(),
        ]);

        // Update conversation with CSAT rating
        if ($survey->conversation) {
            $survey->conversation->update([
                'csat_rating' => $validated['rating'],
            ]);
        }

        // Trigger notification if rating is low (1-2)
        if ($validated['rating'] <= 2) {
            event(new LowCsatRatingReceivedEvent($survey));
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your feedback!',
            'data' => [
                'rating' => $survey->rating,
                'rating_emoji' => $survey->rating_emoji,
                'rating_label' => $survey->rating_label,
                'submitted_at' => $survey->submitted_at->toIso8601String(),
            ],
        ]);
    }
}
