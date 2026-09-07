<?php

namespace Modules\HelpdeskEmailActivity\Enums;

enum EmailStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Suppressed = 'suppressed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => __('helpdeskemailactivity::emaillog.status.queued'),
            self::Sent => __('helpdeskemailactivity::emaillog.status.sent'),
            self::Failed => __('helpdeskemailactivity::emaillog.status.failed'),
            self::Bounced => __('helpdeskemailactivity::emaillog.status.bounced'),
            self::Complained => __('helpdeskemailactivity::emaillog.status.complained'),
            self::Suppressed => __('helpdeskemailactivity::emaillog.status.suppressed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'warning',
            self::Sent => 'success',
            self::Failed => 'danger',
            self::Bounced => 'danger',
            self::Complained => 'danger',
            self::Suppressed => 'secondary',
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
