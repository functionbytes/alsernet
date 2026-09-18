<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\Inbox;

class InboxChannelDefaultsSeeder extends Seeder
{
    private const CHANNEL_DEFAULTS = [
        'whatsapp' => ['color' => '#25D366', 'icon' => 'fab fa-whatsapp'],
        'facebook' => ['color' => '#1877F2', 'icon' => 'fab fa-facebook-messenger'],
        'instagram' => ['color' => '#E1306C', 'icon' => 'fab fa-instagram'],
        'email' => ['color' => '#6B7280', 'icon' => 'far fa-envelope'],
        'web' => ['color' => '#6366F1', 'icon' => 'far fa-comment-dots'],
        'widget' => ['color' => '#6366F1', 'icon' => 'far fa-comment-dots'],
        'sms' => ['color' => '#10B981', 'icon' => 'fas fa-mobile-screen'],
        'telegram' => ['color' => '#0088CC', 'icon' => 'fab fa-telegram'],
    ];

    public function run(): void
    {
        Inbox::query()
            ->where(fn ($q) => $q->whereNull('color')->orWhereNull('icon'))
            ->each(function (Inbox $inbox): void {
                $defaults = self::CHANNEL_DEFAULTS[$inbox->channel_type] ?? null;

                if (! $defaults) {
                    return;
                }

                $updates = array_filter([
                    'color' => $inbox->color === null ? $defaults['color'] : null,
                    'icon' => $inbox->icon === null ? $defaults['icon'] : null,
                ]);

                if (! empty($updates)) {
                    $inbox->update($updates);
                }
            });
    }
}
