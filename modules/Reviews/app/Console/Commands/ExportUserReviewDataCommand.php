<?php

namespace Modules\Reviews\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Reviews\Models\ReviewAiSetting;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewReplyTemplate;
use Modules\Reviews\Models\UserNotificationPreference;

class ExportUserReviewDataCommand extends Command
{
    protected $signature = 'reviews:gdpr-export {user_id}';

    protected $description = 'Exportar todos los datos de reseñas asociados a un usuario (GDPR)';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $user = User::find($userId);

        if (! $user) {
            $this->error("Usuario con ID {$userId} no encontrado.");

            return self::FAILURE;
        }

        $this->info("Recopilando datos GDPR para el usuario #{$userId} ({$user->email})...");

        try {
            $data = $this->collectUserData($user);
            $path = $this->writeExportFile($userId, $data);

            $this->info("Exportacion completada: storage/app/{$path}");

            Log::info('GDPR export completed', ['user_id' => $userId, 'path' => $path]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error al exportar datos: {$e->getMessage()}");
            Log::error('GDPR export failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    private function collectUserData(User $user): array
    {
        $connections = ReviewGoogleConnection::query()
            ->with('locations.reviews.replies')
            ->where('user_id', $user->id)
            ->withTrashed()
            ->get();

        $replies = collect();
        $locations = collect();
        $reviews = collect();

        foreach ($connections as $connection) {
            foreach ($connection->locations as $location) {
                $locations->push($location);
                foreach ($location->reviews as $review) {
                    $reviews->push($review);
                    $userReplies = $review->replies->where('created_by', $user->id);
                    foreach ($userReplies as $reply) {
                        $replies->push($reply);
                    }
                }
            }
        }

        $templates = ReviewReplyTemplate::query()
            ->where('created_by', $user->id)
            ->withTrashed()
            ->get();

        $notificationPreferences = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->get();

        $aiSettings = ReviewAiSetting::getInstance();

        return [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'google_connections' => $connections->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'google_email' => $c->google_email,
                'status' => $c->status?->value,
                'connected_at' => $c->connected_at?->toIso8601String(),
                'revoked_at' => $c->revoked_at?->toIso8601String(),
                'deleted_at' => $c->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'locations' => $locations->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'google_location_id' => $l->google_location_id,
                'address' => $l->address,
                'is_active' => $l->is_active,
            ])->values()->all(),
            'reviews' => $reviews->map(fn ($r) => [
                'id' => $r->id,
                'google_review_id' => $r->google_review_id,
                'reviewer_name' => $r->reviewer_name,
                'star_rating' => $r->star_rating?->value,
                'comment' => $r->comment,
                'review_time' => $r->review_time?->toIso8601String(),
                'synced_at' => $r->synced_at?->toIso8601String(),
            ])->values()->all(),
            'replies_written' => $replies->map(fn ($r) => [
                'id' => $r->id,
                'review_id' => $r->review_id,
                'reply_text' => $r->reply_text,
                'status' => $r->status?->value,
                'published_at' => $r->published_at?->toIso8601String(),
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values()->all(),
            'templates' => $templates->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'body' => $t->body,
                'category' => $t->category,
                'is_active' => $t->is_active,
                'created_at' => $t->created_at?->toIso8601String(),
                'deleted_at' => $t->deleted_at?->toIso8601String(),
            ])->values()->all(),
            'notification_preferences' => $notificationPreferences->map(fn ($p) => [
                'notification_type' => $p->notification_type,
                'channels' => $p->channels,
                'is_enabled' => $p->is_enabled,
                'filters' => $p->filters,
            ])->values()->all(),
            'ai_settings' => $aiSettings ? [
                'provider' => $aiSettings->provider,
                'model' => $aiSettings->model,
                'is_enabled' => $aiSettings->is_enabled,
                'tone' => $aiSettings->tone,
                'language' => $aiSettings->language,
                'max_tokens' => $aiSettings->max_tokens,
                // api_key intentionally excluded
            ] : null,
        ];
    }

    private function writeExportFile(int $userId, array $data): string
    {
        $date = now()->format('Y-m-d');
        $path = "gdpr/user_{$userId}_reviews_{$date}.json";

        Storage::disk('local')->makeDirectory('gdpr');
        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
