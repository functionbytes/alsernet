<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialConversation;
use Modules\HelpdeskSocial\Models\SocialMention;

class AnonymizeSocialUserCommand extends Command
{
    protected $signature = 'helpdesk-social:anonymize-user
                            {--external-user-id= : External user ID on the social platform}
                            {--platform= : Social platform (facebook, instagram, etc.)}';

    protected $description = 'Anonymize all data for a social user across comments, mentions, and conversations';

    public function handle(): int
    {
        $externalUserId = $this->option('external-user-id');
        $platform = $this->option('platform');

        if (! $externalUserId || ! $platform) {
            $this->error('Both --external-user-id and --platform are required.');

            return self::FAILURE;
        }

        $hashedId = hash('sha256', $externalUserId);

        $commentCount = SocialComment::where('external_user_id', $externalUserId)
            ->where('platform', $platform)
            ->update([
                'author_name' => '[anonymized]',
                'author_username' => '[anonymized]',
                'body' => '[anonymized]',
                'external_user_id' => $hashedId,
            ]);

        $mentionCount = SocialMention::where('external_id', $externalUserId)
            ->where('platform', $platform)
            ->update([
                'author_name' => '[anonymized]',
                'author_username' => '[anonymized]',
                'body' => '[anonymized]',
                'external_id' => $hashedId,
            ]);

        $conversationCount = SocialConversation::where('participant_external_id', $externalUserId)
            ->where('platform', $platform)
            ->update([
                'participant_name' => '[anonymized]',
                'participant_external_id' => $hashedId,
            ]);

        $this->info("Anonymized {$commentCount} comments, {$mentionCount} mentions, {$conversationCount} conversations.");

        return self::SUCCESS;
    }
}
