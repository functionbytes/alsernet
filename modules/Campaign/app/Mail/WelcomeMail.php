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
 * Email de bienvenida tras confirmar suscripción.
 *
 * Activado si list.send_welcome_email = 1.
 * Si la lista tiene una plantilla `welcome_template_id` apuntando a un
 * Template, usa su HTML compilado; si no, usa la plantilla Blade básica
 * `campaign::emails.welcome`.
 */
class WelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public CampaignSubscriber $subscriber,
        public CampaignMaillist $list,
    ) {}

    public function envelope(): Envelope
    {
        $from = $this->list->from_email
            ? new Address($this->list->from_email, $this->list->from_name ?: config('app.name'))
            : new Address(config('mail.from.address'), config('mail.from.name'));

        return new Envelope(
            from: $from,
            subject: "Bienvenido a {$this->list->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'campaign::emails.welcome',
            with: [
                'subscriber' => $this->subscriber,
                'list' => $this->list,
                'manageUrl' => route('campaign.manage', $this->subscriber->uid),
            ],
        );
    }
}
