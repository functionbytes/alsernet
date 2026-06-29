<?php

namespace Modules\Blog\Enums;

enum TranslationStatus: string
{
    case AutoTranslated = 'auto_translated';
    case Manual = 'manual';
    case Reviewed = 'reviewed';
    case Stale = 'stale';

    public function label(): string
    {
        return match ($this) {
            self::AutoTranslated => 'Auto-traducido',
            self::Manual => 'Manual',
            self::Reviewed => 'Revisado',
            self::Stale => 'Obsoleto',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AutoTranslated => 'fas fa-robot',
            self::Manual => 'fas fa-pen',
            self::Reviewed => 'fas fa-check-circle',
            self::Stale => 'fas fa-exclamation-triangle',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AutoTranslated => 'info',
            self::Manual => 'primary',
            self::Reviewed => 'success',
            self::Stale => 'warning',
        };
    }
}
