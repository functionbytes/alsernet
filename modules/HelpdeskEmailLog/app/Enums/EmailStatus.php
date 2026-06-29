<?php

namespace Modules\HelpdeskEmailLog\Enums;

enum EmailStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => __('helpdeskemaillog::emaillog.status.queued'),
            self::Sent => __('helpdeskemaillog::emaillog.status.sent'),
            self::Failed => __('helpdeskemaillog::emaillog.status.failed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'warning',
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
