<?php

namespace Modules\Social\Enums;

enum SocialNetwork: string
{
    case FACEBOOK = 'facebook';
    case INSTAGRAM = 'instagram';
    case LINKEDIN = 'linkedin';
    case TWITTER = 'twitter';
    case TIKTOK = 'tiktok';
    case YOUTUBE = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::FACEBOOK => 'Facebook',
            self::INSTAGRAM => 'Instagram',
            self::LINKEDIN => 'LinkedIn',
            self::TWITTER => 'X (Twitter)',
            self::TIKTOK => 'TikTok',
            self::YOUTUBE => 'YouTube',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FACEBOOK => 'ti ti-brand-facebook',
            self::INSTAGRAM => 'ti ti-brand-instagram',
            self::LINKEDIN => 'ti ti-brand-linkedin',
            self::TWITTER => 'ti ti-brand-x',
            self::TIKTOK => 'ti ti-brand-tiktok',
            self::YOUTUBE => 'ti ti-brand-youtube',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FACEBOOK => '#1877F2',
            self::INSTAGRAM => '#E4405F',
            self::LINKEDIN => '#0A66C2',
            self::TWITTER => '#000000',
            self::TIKTOK => '#000000',
            self::YOUTUBE => '#FF0000',
        };
    }
}
