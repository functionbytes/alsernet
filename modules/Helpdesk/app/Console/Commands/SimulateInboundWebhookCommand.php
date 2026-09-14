<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Services\FacebookMessengerService;
use Modules\Helpdesk\Services\InstagramService;
use Modules\Helpdesk\Services\Webhooks\FacebookMessageProcessor;
use Modules\Helpdesk\Services\Webhooks\InstagramMessageProcessor;
use Modules\Helpdesk\Services\Webhooks\WhatsAppMessageProcessor;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

class SimulateInboundWebhookCommand extends Command
{
    protected $signature = 'helpdesk:simulate-webhook
        {--channel=whatsapp : Channel to simulate (whatsapp|facebook|instagram)}
        {--phone= : WhatsApp phone number (e.g. 34612345678)}
        {--psid= : Facebook PSID}
        {--igid= : Instagram user ID}
        {--name= : Sender display name}
        {--message=Hola, necesito información : Message text}';

    protected $description = 'Simulate an inbound webhook from WhatsApp, Facebook or Instagram (testing only)';

    public function handle(
        WhatsAppBusinessService $whatsappService,
        FacebookMessengerService $facebookService,
        InstagramService $instagramService,
        WhatsAppMessageProcessor $waProcessor,
        FacebookMessageProcessor $fbProcessor,
        InstagramMessageProcessor $igProcessor,
    ): int {
        $channel = $this->option('channel');

        $item = match ($channel) {
            'whatsapp' => $this->simulateWhatsApp($whatsappService, $waProcessor),
            'facebook' => $this->simulateFacebook($facebookService, $fbProcessor),
            'instagram' => $this->simulateInstagram($instagramService, $igProcessor),
            default => null,
        };

        if ($item === false) {
            $this->error("Unknown channel: {$channel}. Use whatsapp, facebook, or instagram.");

            return self::FAILURE;
        }

        if ($item === null) {
            $this->warn('Message skipped (duplicate or invalid payload).');

            return self::SUCCESS;
        }

        $item->load('conversation.customer');
        $customer = $item->conversation->customer;
        $conversation = $item->conversation;

        $this->info("Channel    : {$channel}");
        $this->info("Customer   : {$customer->name} (ID #{$customer->id})");
        $this->info("Conversation: #{$conversation->id} — {$conversation->subject}");
        $this->info("Item       : #{$item->id} — \"{$item->body}\"");

        return self::SUCCESS;
    }

    private function simulateWhatsApp(WhatsAppBusinessService $service, WhatsAppMessageProcessor $processor): mixed
    {
        $phone = $this->option('phone') ?: '34600000001';
        $name = $this->option('name') ?: 'Test WhatsApp';
        $message = $this->option('message');
        $messageId = 'wamid.simulate_'.uniqid();

        $rawPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WHATSAPP_BUSINESS_ACCOUNT_ID',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '+15551234567',
                            'phone_number_id' => 'PHONE_NUMBER_ID',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => $name],
                            'wa_id' => $phone,
                        ]],
                        'messages' => [[
                            'from' => $phone,
                            'id' => $messageId,
                            'timestamp' => (string) time(),
                            'type' => 'text',
                            'text' => ['body' => $message],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        $events = $service->parseWebhookPayload($rawPayload);

        foreach ($events as $event) {
            if ($event['type'] === 'message') {
                return $processor->process($event);
            }
        }

        return null;
    }

    private function simulateFacebook(FacebookMessengerService $service, FacebookMessageProcessor $processor): mixed
    {
        $psid = $this->option('psid') ?: 'PSID_SIMULATE_'.uniqid();
        $message = $this->option('message');
        $messageId = 'm_simulate_'.uniqid();

        $rawPayload = [
            'object' => 'page',
            'entry' => [[
                'id' => 'PAGE_ID',
                'time' => time(),
                'messaging' => [[
                    'sender' => ['id' => $psid],
                    'recipient' => ['id' => 'PAGE_ID'],
                    'timestamp' => time(),
                    'message' => [
                        'mid' => $messageId,
                        'text' => $message,
                    ],
                ]],
            ]],
        ];

        $events = $service->parseWebhookPayload($rawPayload);

        foreach ($events as $event) {
            if ($event['type'] === 'message') {
                return $processor->process($event);
            }
        }

        return null;
    }

    private function simulateInstagram(InstagramService $service, InstagramMessageProcessor $processor): mixed
    {
        $igid = $this->option('igid') ?: 'IG_SIMULATE_'.uniqid();
        $message = $this->option('message');
        $messageId = 'ig_msg_simulate_'.uniqid();

        $rawPayload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => 'IG_ACCOUNT_ID',
                'time' => time(),
                'messaging' => [[
                    'sender' => ['id' => $igid],
                    'recipient' => ['id' => 'IG_ACCOUNT_ID'],
                    'timestamp' => time(),
                    'message' => [
                        'mid' => $messageId,
                        'text' => $message,
                    ],
                ]],
            ]],
        ];

        $events = $service->parseWebhookPayload($rawPayload);

        foreach ($events as $event) {
            if (in_array($event['type'], ['message', 'story_reply'], true)) {
                return $processor->process($event);
            }
        }

        return null;
    }
}
