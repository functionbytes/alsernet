<?php

namespace Modules\Campaign\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;

/**
 * Email de confirmación de suscripción (doble opt-in).
 *
 * Sustituye el `Mail::raw()` que tenía SubscriptionController. Usa
 * plantilla Blade `campaign::emails.subscribe_confirmation` que se puede
 * customizar por instalación.
 */
class SubscribeConfirmationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public CampaignSubscriber $subscriber,
        public CampaignMaillist $list,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->list->from_email
                ? new Address($this->list->from_email, $this->list->from_name ?: config('app.name'))
                : new Address(config('mail.from.address'), config('mail.from.name')),
            subject: "Confirma tu suscripción a {$this->list->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'campaign::emails.subscribe_confirmation',
            with: [
                'subscriber' => $this->subscriber,
                'list' => $this->list,
                'confirmUrl' => route('campaign.subscribe.confirm', $this->token),
            ],
        );
    }
}
