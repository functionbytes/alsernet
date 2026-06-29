<?php

namespace Modules\Blog\Enums;

enum PostStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicado',
            self::Pending => 'Pendiente',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge bg-secondary',
            self::Published => 'badge bg-success',
            self::Pending => 'badge bg-warning text-dark',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases()),
            'label',
            'value'
        );
    }
}
