<?php

namespace Modules\Campaign\Services;

/**
 * Clasifica bounces en hard, soft, block o unknown
 * basado en el mensaje de error del servidor.
 */
class BounceClassifier
{
    protected array $patterns = [
        'hard' => [
            'user unknown',
            'recipient unknown',
            'mailbox unavailable',
            'invalid recipient',
            'address rejected',
            'no such user',
            'does not exist',
            '5.1.1',
            '5.1.3',
            '5.1.6',
        ],
        'soft' => [
            'mailbox full',
            'quota exceeded',
            'temporary failure',
            'defer',
            'greylist',
            'try again',
            '4.2.2',
            '4.3.1',
            '4.4.1',
            '4.4.2',
            '4.4.5',
            '4.7.1',
        ],
        'block' => [
            'blocked',
            'blacklist',
            'spam',
            'rejected due to policy',
            'access denied',
            '5.7.1',
            '5.7.2',
            '5.7.6',
            '5.7.7',
        ],
    ];

    public function classify(?string $errorMessage): array
    {
        if (empty($errorMessage)) {
            return ['type' => 'unknown', 'category' => 'unclassified'];
        }

        $lower = strtolower($errorMessage);

        foreach ($this->patterns as $type => $list) {
            foreach ($list as $pattern) {
                if (str_contains($lower, $pattern)) {
                    return [
                        'type' => $type,
                        'category' => $this->category($type, $lower),
                    ];
                }
            }
        }

        return ['type' => 'unknown', 'category' => 'unclassified'];
    }

    public function shouldRetry(string $bounceType): bool
    {
        return in_array($bounceType, ['soft', 'unknown'], true);
    }

    protected function category(string $type, string $message): string
    {
        return match ($type) {
            'hard' => match (true) {
                str_contains($message, '5.1.1') => 'bad_mailbox',
                str_contains($message, '5.1.3') => 'bad_syntax',
                str_contains($message, '5.1.6') => 'mailbox_moved',
                default => 'bad_mailbox',
            },
            'soft' => match (true) {
                str_contains($message, 'full') || str_contains($message, 'quota') => 'mailbox_full',
                str_contains($message, 'greylist') => 'greylisted',
                default => 'general',
            },
            'block' => match (true) {
                str_contains($message, 'spam') => 'spam_block',
                str_contains($message, 'blacklist') => 'blacklisted',
                default => 'policy_block',
            },
            default => 'unclassified',
        };
    }
}
