<?php

namespace Modules\EcommercePayment\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDING = 'refunding';
    case REFUNDED = 'refunded';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::COMPLETED => 'Completado',
            self::FAILED => 'Fallido',
            self::REFUNDING => 'Reembolsando',
            self::REFUNDED => 'Reembolsado',
            self::CANCELED => 'Cancelado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'badge bg-warning',
            self::COMPLETED => 'badge bg-success',
            self::FAILED => 'badge bg-danger',
            self::REFUNDING => 'badge bg-info',
            self::REFUNDED => 'badge bg-secondary',
            self::CANCELED => 'badge bg-dark',
        };
    }
}
