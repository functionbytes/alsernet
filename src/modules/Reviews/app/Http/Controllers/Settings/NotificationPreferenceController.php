<?php

namespace Modules\Reviews\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewReply;
use Modules\Reviews\Models\UserNotificationPreference;
use Modules\Reviews\Notifications\ConnectionExpiringNotification;
use Modules\Reviews\Notifications\DailyDigestNotification;
use Modules\Reviews\Notifications\ExportReadyNotification;
use Modules\Reviews\Notifications\NewNegativeReviewNotification;
use Modules\Reviews\Notifications\NewReviewNotification;
use Modules\Reviews\Notifications\PositiveReviewNotification;
use Modules\Reviews\Notifications\ReplyPublishedNotification;
use Modules\Reviews\Services\NotificationService;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
        $this->middleware('can:reviews.settings.manage');
    }

    public function index(): View
    {
        $user = auth()->user();

        $this->notificationService->initializeDefaultPreferences($user);

        $preferences = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('notification_type');

        $types = NotificationService::getAvailableTypes();
        $channels = NotificationService::getAvailableChannels();

        return view('reviews::settings.notifications', compact('preferences', 'types', 'channels'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string',
            'preferences.*.is_enabled' => 'required|boolean',
            'preferences.*.channels' => 'nullable|array',
            'preferences.*.channels.*' => 'nullable|string|in:mail,database,slack',
            'preferences.*.filters' => 'nullable|array',
            'preferences.*.filters.min_rating' => 'nullable|integer|min:1|max:5',
            'preferences.*.filters.max_rating' => 'nullable|integer|min:1|max:5',
            'preferences.*.filters.location_ids' => 'nullable|array',
        ]);

        $user = auth()->user();

        foreach ($validated['preferences'] as $preferenceData) {
            UserNotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $preferenceData['notification_type'],
                ],
                [
                    'is_enabled' => $preferenceData['is_enabled'],
                    'channels' => $preferenceData['channels'] ?? [],
                    'filters' => $preferenceData['filters'] ?? null,
                ]
            );
        }

        activity()
            ->causedBy($user)
            ->log('Notification preferences updated');

        return redirect()
            ->route('settings.reviews.notifications.index')
            ->with('success', 'Preferencias de notificación actualizadas correctamente');
    }

    public function test(Request $request, string $type): JsonResponse
    {
        $user = auth()->user();

        $sampleReview = Review::query()->first();
        $sampleReply = ReviewReply::query()->first();
        $sampleConnection = ReviewGoogleConnection::query()->first();

        $notification = match ($type) {
            NotificationService::TYPE_NEW_REVIEW => $sampleReview
                ? new NewReviewNotification($sampleReview)
                : null,
            NotificationService::TYPE_NEGATIVE_REVIEW => $sampleReview
                ? new NewNegativeReviewNotification($sampleReview)
                : null,
            NotificationService::TYPE_POSITIVE_REVIEW => $sampleReview
                ? new PositiveReviewNotification($sampleReview)
                : null,
            NotificationService::TYPE_EXPORT_READY => new ExportReadyNotification('test-export.xlsx'),
            NotificationService::TYPE_REPLY_PUBLISHED => $sampleReply
                ? new ReplyPublishedNotification($sampleReply)
                : null,
            NotificationService::TYPE_CONNECTION_EXPIRING => $sampleConnection
                ? new ConnectionExpiringNotification($sampleConnection, 7)
                : null,
            NotificationService::TYPE_DAILY_DIGEST => new DailyDigestNotification([
                'new_reviews_count' => 5,
                'average_rating' => 4.2,
                'pending_replies_count' => 3,
                'negative_reviews_count' => 1,
                'top_location' => ['name' => 'Test Location', 'count' => 3],
            ]),
            default => null,
        };

        if (! $notification) {
            return response()->json([
                'message' => 'Unable to send test notification. Please ensure sample data exists.',
            ], 400);
        }

        $user->notify($notification);

        activity()
            ->causedBy($user)
            ->withProperties(['type' => $type])
            ->log('Test notification sent');

        return response()->json(['message' => 'Test notification sent successfully']);
    }
}
