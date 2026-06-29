<?php

namespace Modules\Reviews\Enums;

enum ReplyStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::PENDING_APPROVAL => 'Pendiente de aprobacion',
            self::APPROVED => 'Aprobado',
            self::SCHEDULED => 'Programado',
            self::PUBLISHED => 'Publicado',
            self::FAILED => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING_APPROVAL => 'info',
            self::APPROVED => 'warning',
            self::SCHEDULED => 'primary',
            self::PUBLISHED => 'success',
            self::FAILED => 'danger',
        };
    }
}
