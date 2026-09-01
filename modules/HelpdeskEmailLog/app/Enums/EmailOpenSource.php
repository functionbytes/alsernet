<?php

namespace Modules\HelpdeskEmailLog\Enums;

/**
 * Origen de una fila de EmailLogOpen (ver
 * 2026_09_01_050000_add_source_to_email_log_opens_table): 'pixel' es el gif
 * de 1x1 servido por EmailOpenTrackingController (único origen hasta ahora),
 * 'provider' es un evento 'open' recibido de un webhook de proveedor —
 * todavía no ingerido por EmailProviderWebhookController, que hoy solo
 * procesa bounce/complaint.
 */
enum EmailOpenSource: string
{
    case Pixel = 'pixel';
    case Provider = 'provider';

    public function label(): string
    {
        return match ($this) {
            self::Pixel => __('helpdeskemaillog::emaillog.opens.source.pixel'),
            self::Provider => __('helpdeskemaillog::emaillog.opens.source.provider'),
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
