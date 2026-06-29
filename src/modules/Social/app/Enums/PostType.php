<?php

namespace Modules\Social\Enums;

enum PostType: string
{
    case TEXT = 'text';
    case LINK = 'link';
    case MEDIA = 'media';
    case VIDEO = 'video';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Texto',
            self::LINK => 'Enlace',
            self::MEDIA => 'Imagen',
            self::VIDEO => 'Video',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TEXT => 'ti ti-text',
            self::LINK => 'ti ti-link',
            self::MEDIA => 'ti ti-photo',
            self::VIDEO => 'ti ti-video',
        };
    }
}
