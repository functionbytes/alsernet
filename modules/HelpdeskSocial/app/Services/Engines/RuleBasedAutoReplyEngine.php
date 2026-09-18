<?php

namespace Modules\HelpdeskSocial\Services\Engines;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Contracts\AutoReplyEngineInterface;
use Modules\HelpdeskSocial\Contracts\Repositories\SocialRuleRepositoryInterface;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Events\SocialCommentReplied;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialTemplate;

class RuleBasedAutoReplyEngine implements AutoReplyEngineInterface
{
    public function __construct(
        private readonly SocialRuleRepositoryInterface $ruleRepository,
        private readonly SocialApiClientInterface $apiClient,
    ) {}

    public function isEnabled(): bool
    {
        return config('helpdesksocial.auto_reply.enabled', true);
    }

    public function evaluate(SocialComment $comment): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! $comment->socialAccount?->auto_reply_enabled) {
            return null;
        }

        $rules = $this->ruleRepository->getActiveForPlatform($comment->platform);

        foreach ($rules as $rule) {
            if ($this->matches($comment, $rule)) {
                return $this->executeActions($comment, $rule);
            }
        }

        return null;
    }

    private function matches(SocialComment $comment, $rule): bool
    {
        $conditions = $rule->conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            $commentValue = match ($field) {
                'intent' => $comment->intent,
                'urgency' => $comment->urgency,
                'platform' => $comment->platform,
                'body' => $comment->body,
                'author_name' => $comment->author_name,
                'is_mention' => $comment->is_mention,
                default => null,
            };

            if (! $this->evaluateCondition($commentValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition(mixed $fieldValue, string $operator, mixed $conditionValue): bool
    {
        return match ($operator) {
            'equals' => $fieldValue == $conditionValue,
            'not_equals' => $fieldValue != $conditionValue,
            'contains' => is_string($fieldValue) && str_contains(mb_strtolower($fieldValue), mb_strtolower((string) $conditionValue)),
            'not_contains' => is_string($fieldValue) && ! str_contains(mb_strtolower($fieldValue), mb_strtolower((string) $conditionValue)),
            'in' => is_array($conditionValue) && in_array($fieldValue, $conditionValue),
            'not_in' => is_array($conditionValue) && ! in_array($fieldValue, $conditionValue),
            'is_empty' => empty($fieldValue),
            'is_not_empty' => ! empty($fieldValue),
            'starts_with' => is_string($fieldValue) && str_starts_with(mb_strtolower($fieldValue), mb_strtolower((string) $conditionValue)),
            'ends_with' => is_string($fieldValue) && str_ends_with(mb_strtolower($fieldValue), mb_strtolower((string) $conditionValue)),
            default => false,
        };
    }

    private function executeActions(SocialComment $comment, $rule): array
    {
        $results = [];
        $actions = $rule->actions ?? [];

        foreach ($actions as $action) {
            $type = $action['type'] ?? '';
            $params = $action['params'] ?? [];

            $result = match ($type) {
                'reply' => $this->actionReply($comment, $params),
                'reply_template' => $this->actionReplyTemplate($comment, $params),
                'hide_comment' => $this->actionHideComment($comment, $params),
                'mark_spam' => $this->actionMarkSpam($comment),
                'escalate' => $this->actionEscalate($comment, $params),
                'assign_to_user' => $this->actionAssignToUser($comment, $params),
                'tag' => $this->actionTag($comment, $params),
                default => ['success' => false, 'error' => "Unknown action type: {$type}"],
            };

            $results[$type] = $result;
        }

        $this->ruleRepository->incrementTrigger($rule);

        return [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'actions' => $results,
            'stop_processing' => $rule->stop_processing,
        ];
    }

    private function actionReply(SocialComment $comment, array $params): array
    {
        $text = $params['text'] ?? '';

        if (blank($text)) {
            return ['success' => false, 'error' => 'Empty reply text'];
        }

        return $this->sendReply($comment, $text);
    }

    private function actionReplyTemplate(SocialComment $comment, array $params): array
    {
        $templateId = $params['template_id'] ?? null;

        if (! $templateId) {
            return ['success' => false, 'error' => 'No template_id provided'];
        }

        $template = SocialTemplate::find($templateId);

        if (! $template) {
            return ['success' => false, 'error' => 'Template not found'];
        }

        $rendered = $template->render([
            'author_name' => $comment->author_name ?? 'Usuario',
            'author_username' => $comment->author_username ?? '',
            'platform' => $comment->platform,
            'body' => $comment->body,
        ]);

        $result = $this->sendReply($comment, $rendered);

        if ($result['success']) {
            $template->incrementUsage();
        }

        return $result;
    }

    private function actionHideComment(SocialComment $comment, array $params): array
    {
        $hidden = $params['hidden'] ?? true;

        try {
            $this->apiClient->hideComment(
                $comment->external_comment_id,
                $hidden,
                $comment->socialAccount->page_access_token
            );

            $comment->forceFill(['is_hidden' => $hidden])->save();

            return ['success' => true, 'hidden' => $hidden];
        } catch (\Throwable $e) {
            Log::error('RuleBasedAutoReplyEngine: hideComment failed', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function actionMarkSpam(SocialComment $comment): array
    {
        $comment->markAsSpam();

        return ['success' => true, 'status' => 'spam'];
    }

    private function actionEscalate(SocialComment $comment, array $params): array
    {
        $userId = $params['user_id'] ?? null;
        $comment->markAsEscalated($userId);

        return ['success' => true, 'status' => 'escalated'];
    }

    private function actionAssignToUser(SocialComment $comment, array $params): array
    {
        $userId = $params['user_id'] ?? null;

        if (! $userId) {
            return ['success' => false, 'error' => 'No user_id provided'];
        }

        $comment->assignTo($userId);

        return ['success' => true, 'assigned_to' => $userId];
    }

    private function actionTag(SocialComment $comment, array $params): array
    {
        $tags = $params['tags'] ?? [];
        $meta = $comment->metadata ?? [];
        $meta['auto_tags'] = array_merge($meta['auto_tags'] ?? [], $tags);
        $comment->update(['metadata' => $meta]);

        return ['success' => true, 'tags' => $tags];
    }

    private function sendReply(SocialComment $comment, string $text): array
    {
        try {
            $account = $comment->socialAccount;
            $token = $account->page_access_token;

            if (in_array($comment->platform, ['facebook', 'instagram'], true)) {
                $replyId = $this->apiClient->replyToComment($comment->external_comment_id, $text, $token, $comment->platform);
            } else {
                return ['success' => false, 'error' => 'Unsupported platform for comment reply'];
            }

            if (! $replyId) {
                return ['success' => false, 'error' => 'API returned no reply ID'];
            }

            $comment->markAsReplied($text, null, $replyId, 'auto');
            $comment = $comment->fresh();

            SocialCommentReplied::dispatch($comment);

            return ['success' => true, 'external_reply_id' => $replyId];
        } catch (\Throwable $e) {
            Log::error('RuleBasedAutoReplyEngine: sendReply failed', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
