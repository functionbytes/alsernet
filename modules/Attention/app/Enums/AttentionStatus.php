<?php

namespace Modules\Attention\Enums;

enum AttentionStatus: string
{
    case RECEIVED = 'received';
    case IN_PROCESS = 'in_process';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::RECEIVED => 'Recibido',
            self::IN_PROCESS => 'En Proceso',
            self::RESOLVED => 'Resuelto',
            self::CLOSED => 'Cerrado',
        };
    }

    /**
     * Get badge color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::RECEIVED => 'blue',
            self::IN_PROCESS => 'yellow',
            self::RESOLVED => 'green',
            self::CLOSED => 'gray',
        };
    }

    /**
     * Get all status options for select
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ])
            ->toArray();
    }
}
