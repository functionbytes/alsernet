<?php

namespace Modules\Ecommerce\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::PROCESSING => 'Procesando',
            self::SHIPPED => 'Enviado',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'badge bg-warning text-dark',
            self::PROCESSING => 'badge bg-info',
            self::SHIPPED => 'badge bg-primary',
            self::COMPLETED => 'badge bg-success',
            self::CANCELLED => 'badge bg-danger',
        };
    }
}
