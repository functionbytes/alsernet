<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialConversation;
use Modules\HelpdeskSocial\Models\SocialMention;

class ExportSocialUserDataCommand extends Command
{
    protected $signature = 'helpdesk-social:export-user-data
                            {--external-user-id= : External user ID on the social platform}
                            {--platform= : Social platform (facebook, instagram, etc.)}';

    protected $description = 'Export all comments, mentions, and conversations for a social user to JSON';

    public function handle(): int
    {
        $externalUserId = $this->option('external-user-id');
        $platform = $this->option('platform');

        if (! $externalUserId || ! $platform) {
            $this->error('Both --external-user-id and --platform are required.');

            return self::FAILURE;
        }

        $comments = SocialComment::where('external_user_id', $externalUserId)
            ->where('platform', $platform)
            ->get();

        $mentions = SocialMention::where('external_id', $externalUserId)
            ->where('platform', $platform)
            ->get();

        $conversations = SocialConversation::where('participant_external_id', $externalUserId)
            ->where('platform', $platform)
            ->get();

        $data = [
            'external_user_id' => $externalUserId,
            'platform' => $platform,
            'exported_at' => now()->toIso8601String(),
            'comments' => $comments->toArray(),
            'mentions' => $mentions->toArray(),
            'conversations' => $conversations->toArray(),
        ];

        $filename = "social-user-data-{$platform}-{$externalUserId}-".now()->format('Y-m-d_His').'.json';
        $path = storage_path("app/exports/{$filename}");

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        $this->info("Exported {$comments->count()} comments, {$mentions->count()} mentions, {$conversations->count()} conversations.");
        $this->info("File saved to: {$path}");

        return self::SUCCESS;
    }
}
