<?php

namespace Modules\Campaign\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * Notificación al admin cuando hay un cambio (subscribe / unsubscribe).
 * Disparado si la lista tiene mail_subscribe / mail_unsubscribe configurado.
 */
class AdminSubscribeNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public CampaignSubscriber $subscriber,
        public CampaignMaillist $list,
        public string $event,  // 'subscribed' | 'unsubscribed'
    ) {}

    public function envelope(): Envelope
    {
        $verb = $this->event === 'subscribed' ? 'Nueva suscripción' : 'Desuscripción';

        return new Envelope(
            subject: "[{$this->list->name}] {$verb}: {$this->subscriber->email}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'campaign::emails.admin_subscribe',
            with: [
                'subscriber' => $this->subscriber,
                'list' => $this->list,
                'event' => $this->event,
            ],
        );
    }
}
