<?php

namespace Modules\HelpdeskEmailLog\Enums;

enum SuppressionReason: string
{
    case HardBounce = 'hard_bounce';
    case Complaint = 'complaint';
    case Unsubscribed = 'unsubscribed';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::HardBounce => __('helpdeskemaillog::emaillog.suppressions.reason.hard_bounce'),
            self::Complaint => __('helpdeskemaillog::emaillog.suppressions.reason.complaint'),
            self::Unsubscribed => __('helpdeskemaillog::emaillog.suppressions.reason.unsubscribed'),
            self::Manual => __('helpdeskemaillog::emaillog.suppressions.reason.manual'),
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
