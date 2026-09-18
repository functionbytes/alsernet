<?php

namespace Modules\Media\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Media\Mail\MediaDigestMail;
use Modules\Media\Models\MediaFile;

class SendMediaDigestCommand extends Command
{
    protected $signature = 'media:send-digest {--frequency=weekly}';

    protected $description = 'Envía digest de actividad Media (daily/weekly/monthly) a usuarios';

    public function handle(): int
    {
        $frequency = $this->option('frequency');

        $since = match ($frequency) {
            'daily' => now()->subDay(),
            'monthly' => now()->subMonth(),
            default => now()->subWeek(),
        };

        $userIds = MediaFile::query()
            ->where('created_at', '>=', $since)
            ->distinct()
            ->pluck('user_id');

        $users = User::query()->whereIn('id', $userIds)->get();

        $queued = 0;

        foreach ($users as $user) {
            $filesUploaded = MediaFile::query()
                ->where('user_id', $user->id)
                ->where('created_at', '>=', $since)
                ->count();

            if ($filesUploaded === 0) {
                continue;
            }

            $stats = [
                'files_uploaded' => $filesUploaded,
                'storage_used' => MediaFile::query()->where('user_id', $user->id)->sum('size'),
                'period' => $frequency,
                'since' => $since,
            ];

            try {
                Mail::to($user)->queue(new MediaDigestMail($user, $stats));
                $queued++;
            } catch (\Throwable $e) {
                $this->warn("Failed to queue digest for user {$user->id}: {$e->getMessage()}");
            }
        }

        $this->info("Digests encolados: {$queued}");

        return self::SUCCESS;
    }
}
