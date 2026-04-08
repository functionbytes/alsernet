<?php

namespace Modules\Helpdesk\Jobs\Helpdesks;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketMail;
use Modules\Helpdesk\Models\TicketStatus;
use PhpImap\IncomingMailAttachment;
use PhpImap\Mailbox;

class FetchTicketEmailsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct()
    {
        $this->queue = 'helpdesk-scheduled';
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('fetch-ticket-emails'))->dontRelease()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->fetchEmails();
        } catch (\Exception $e) {
            Log::error('Error fetching ticket emails: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Fetch emails from IMAP mailbox and process them.
     */
    protected function fetchEmails(): void
    {
        $config = config('helpdesk.email.imap');

        if (! $config || ! $config['enabled']) {
            Log::info('IMAP email fetching is disabled');

            return;
        }

        try {
            $mailbox = new Mailbox(
                $config['connection'],
                $config['username'],
                $config['password'],
                storage_path('app/imap-attachments'),
                'UTF-8'
            );

            // Fetch unseen messages
            $messageIds = $mailbox->searchMailbox('UNSEEN');

            if (empty($messageIds)) {
                Log::info('No new emails to fetch');

                return;
            }

            foreach ($messageIds as $messageId) {
                try {
                    $message = $mailbox->getMail($messageId);
                    $this->processIncomingEmail($message);

                    // Mark as read after processing
                    $mailbox->setFlag($messageId, 'Seen');
                } catch (\Exception $e) {
                    Log::error("Error processing email {$messageId}: ".$e->getMessage());

                    continue;
                }
            }

            $mailbox->closeMailbox();
        } catch (\Exception $e) {
            Log::error('IMAP connection error: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Process a single incoming email message.
     */
    protected function processIncomingEmail($message): void
    {
        // Parse email data
        $parsed = [
            'message_id' => $message->messageId ?? $this->generateMessageId(),
            'in_reply_to' => $message->inReplyTo ?? null,
            'references' => $message->references ?? null,
            'from' => $message->from ?? null,
            'to' => $message->to ?? null,
            'cc' => $message->cc ?? null,
            'bcc' => $message->bcc ?? null,
            'subject' => $message->subject ?? 'Sin asunto',
            'body_text' => $message->plainTextBody ?? '',
            'body_html' => $message->htmlBody ?? null,
            'headers' => $this->extractHeaders($message),
            'raw_email' => $message->raw ?? null,
        ];

        // Extract attachments
        $parsed['attachments'] = $this->parseAttachments($message);

        // Find or create ticket
        $ticket = $this->findOrCreateTicket($parsed);

        if (! $ticket) {
            Log::warning('Could not create ticket for email: '.$parsed['subject']);

            return;
        }

        // Create TicketMail record
        $ticketMail = TicketMail::createFromInbound($parsed, $ticket);

        // Create a TicketItem for the timeline (customer message)
        $item = $ticket->items()->create([
            'type' => 'message',
            'author_id' => $ticket->customer_id,
            'body' => $parsed['body_text'],
            'html_body' => $parsed['body_html'],
            'is_internal' => false,
        ]);

        // Link mail to item
        $ticketMail->update(['ticket_item_id' => $item->id]);

        // Update last message timestamp
        $ticket->update(['last_message_at' => now()]);

        Log::info("Email processed for ticket #{$ticket->ticket_number}");
    }

    /**
     * Find existing ticket or create new one for email.
     */
    protected function findOrCreateTicket(array $parsed): ?Ticket
    {
        // Try to find by Message-ID threading first
        if ($parsed['in_reply_to']) {
            $existingMail = TicketMail::where('message_id', $parsed['in_reply_to'])->first();
            if ($existingMail) {
                return $existingMail->ticket;
            }
        }

        // Try to find by ticket number in subject (e.g., "Re: Ticket #TCK-2025-00123")
        if (preg_match('/#(TCK-\d{4}-\d{5})/', $parsed['subject'], $matches)) {
            $ticket = Ticket::where('ticket_number', $matches[1])->first();
            if ($ticket) {
                return $ticket;
            }
        }

        // Try to find customer by email
        $fromEmail = $this->extractEmailAddress($parsed['from']);
        $customer = Customer::where('email', $fromEmail)->first();

        if (! $customer) {
            // Create new customer
            $fromName = $this->extractEmailName($parsed['from']);
            $customer = Customer::create([
                'email' => $fromEmail,
                'name' => $fromName ?: $fromEmail,
            ]);
            Log::info("Created new customer: {$fromEmail}");
        }

        // Create new ticket
        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'subject' => $parsed['subject'],
            'description' => $parsed['body_text'] ?? $parsed['body_html'],
            'source' => 'email',
            'status_id' => TicketStatus::where('is_default', true)->first()?->id ?? 1,
            'priority' => $this->detectPriority($parsed['subject']),
        ]);

        Log::info("Created new ticket #{$ticket->ticket_number} from email");

        return $ticket;
    }

    /**
     * Parse attachments from email message.
     */
    protected function parseAttachments($message): array
    {
        $attachments = [];

        if (empty($message->attachments)) {
            return $attachments;
        }

        foreach ($message->attachments as $attachment) {
            try {
                $filename = $attachment->name ?? 'attachment';
                $filePath = $this->saveAttachment($attachment);

                if ($filePath) {
                    $absolutePath = storage_path('app/'.$filePath);
                    $size = null;

                    try {
                        $size = filesize($absolutePath) ?: null;
                    } catch (\Throwable) {
                        // File may not be readable; size remains null
                    }

                    $attachments[] = [
                        'filename' => $filename,
                        'url' => asset('storage/'.$filePath),
                        'size' => $size,
                        'mime' => mime_content_type($absolutePath),
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Error processing attachment: '.$e->getMessage());
            }
        }

        return $attachments;
    }

    /**
     * Save attachment to storage.
     */
    protected function saveAttachment(IncomingMailAttachment $attachment): ?string
    {
        try {
            $filename = $attachment->name ?? time().'_'.random_int(1000, 9999);
            $basePath = config('helpdesk.attachments.path', 'helpdesk/attachments');
            $path = $basePath.'/'.date('Y/m/d').'/'.$filename;

            // Save to storage
            Storage::disk('local')->put($path, $attachment->getAttachmentBody());

            return $path;
        } catch (\Exception $e) {
            Log::error('Error saving attachment: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Extract email address from name+email format.
     */
    protected function extractEmailAddress(string $from): string
    {
        // Handle "Name <email@domain.com>" format
        if (preg_match('/<(.+?)>/', $from, $matches)) {
            return $matches[1];
        }

        // Return as-is if already just email
        return trim($from);
    }

    /**
     * Extract name from name+email format.
     */
    protected function extractEmailName(string $from): ?string
    {
        // Handle "Name <email@domain.com>" format
        if (preg_match('/^(.+?)\s*</', $from, $matches)) {
            return trim($matches[1], ' "\'');
        }

        return null;
    }

    /**
     * Extract important headers from message.
     */
    protected function extractHeaders($message): array
    {
        return [
            'Message-ID' => $message->messageId ?? null,
            'In-Reply-To' => $message->inReplyTo ?? null,
            'References' => $message->references ?? null,
            'Subject' => $message->subject ?? null,
            'Date' => $message->date ?? null,
        ];
    }

    /**
     * Detect priority from subject keywords.
     */
    protected function detectPriority(string $subject): string
    {
        $subject = strtolower($subject);

        if (str_contains($subject, 'urgent') || str_contains($subject, 'crítico')) {
            return 'high';
        }

        if (str_contains($subject, 'baja') || str_contains($subject, 'low')) {
            return 'low';
        }

        return 'normal';
    }

    /**
     * Generate a unique Message-ID.
     */
    protected function generateMessageId(): string
    {
        return '<'.uniqid().'@'.config('app.name').'>';
    }
}
