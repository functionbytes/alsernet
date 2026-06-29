<?php

namespace Modules\Attention\Enums;

enum ResponseType: string
{
    case EMAIL = 'email';
    case IN_PERSON = 'presencial';
    case PHYSICAL_MAIL = 'correo_fisico';
    case PHONE = 'telefono';
    case NOT_REQUIRED = 'no_requiere';

    /**
     * Get human readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Correo Electrónico',
            self::IN_PERSON => 'Presencial',
            self::PHYSICAL_MAIL => 'Correo Físico',
            self::PHONE => 'Teléfono',
            self::NOT_REQUIRED => 'No Requiere Respuesta',
        };
    }

    /**
     * Get all options for select
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->toArray();
    }
}
