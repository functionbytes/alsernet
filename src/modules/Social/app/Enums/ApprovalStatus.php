<?php

namespace Modules\Social\Enums;

enum ApprovalStatus: string
{
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::PENDING_REVIEW => 'Pendiente de Revisión',
            self::APPROVED => 'Aprobado',
            self::REJECTED => 'Rechazado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING_REVIEW => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'ti ti-file',
            self::PENDING_REVIEW => 'ti ti-clock-pause',
            self::APPROVED => 'ti ti-circle-check',
            self::REJECTED => 'ti ti-circle-x',
        };
    }
}
