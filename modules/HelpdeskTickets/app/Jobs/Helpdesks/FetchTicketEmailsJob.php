<?php

namespace Modules\HelpdeskTickets\Jobs\Helpdesks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketService;
use PhpImap\IncomingMailAttachment;
use PhpImap\Mailbox;

class FetchTicketEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    protected ?TicketService $ticketService = null;

    public function __construct()
    {
        $this->queue = 'helpdesk-scheduled';
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('fetch-ticket-emails'))->dontRelease()];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FetchTicketEmailsJob permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Execute the job. Procesa las conexiones IMAP definidas en el setting
     * `incoming_email` (multi-buzón); si no hay ninguna, cae al buzón único de
     * config('helpdesk.email.imap') para no romper instalaciones existentes.
     */
    public function handle(?TicketService $ticketService = null): void
    {
        $this->ticketService = $ticketService;

        try {
            $connections = $this->incomingConnections();

            if (empty($connections)) {
                $this->fetchEmails();

                return;
            }

            foreach ($connections as $connection) {
                $createTickets = (bool) ($connection['create_tickets'] ?? false);
                $createReplies = (bool) ($connection['create_replies'] ?? false);

                // Sin ninguna acción habilitada no hay nada que hacer con este buzón.
                if (! $createTickets && ! $createReplies) {
                    continue;
                }

                try {
                    $this->processConnection($connection);
                } catch (\Throwable $e) {
                    Log::error('FetchTicketEmailsJob: error procesando conexión', [
                        'connection' => $connection['name'] ?? '?',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching ticket emails: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Conexiones IMAP entrantes definidas en el setting `incoming_email`.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function incomingConnections(): array
    {
        $raw = DB::table('settings')->where('key', 'incoming_email')->value('value');

        if (! $raw) {
            return [];
        }

        $data = json_decode((string) $raw, true);

        return $data['imap']['connections'] ?? [];
    }

    /**
     * Conecta a un buzón IMAP concreto y procesa sus mensajes no leídos.
     *
     * @param  array<string, mixed>  $connection
     */
    protected function processConnection(array $connection): void
    {
        $encryption = $connection['encryption'] ?? 'ssl';
        $folder = $connection['folder'] ?? 'INBOX';
        $connectionString = '{'.$connection['host'].':'.($connection['port'] ?? 993).'/imap/'.$encryption.'}'.$folder;

        $mailbox = new Mailbox(
            $connectionString,
            $connection['username'] ?? '',
            $connection['password'] ?? '',
            storage_path('app/imap-attachments'),
            'UTF-8'
        );

        try {
            foreach ($mailbox->searchMailbox('UNSEEN') as $messageId) {
                try {
                    $this->processIncomingEmail($mailbox->getMail($messageId), $connection);
                    $mailbox->setFlag($messageId, 'Seen');
                } catch (\Throwable $e) {
                    Log::error("Error processing email {$messageId}: ".$e->getMessage());
                }
            }
        } finally {
            $mailbox->closeMailbox();
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
    protected function processIncomingEmail($message, array $connection = []): void
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
        $ticket = $this->findOrCreateTicket($parsed, $connection);

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
    protected function findOrCreateTicket(array $parsed, array $connection = []): ?Ticket
    {
        // Try to find by Message-ID threading first
        if ($parsed['in_reply_to']) {
            $existingMail = TicketMail::where('message_id', $parsed['in_reply_to'])->first();
            if ($existingMail) {
                return $existingMail->ticket;
            }
        }

        // Resolve the sender address up front: it is required both for ticket
        // threading verification and for customer lookup/creation.
        $rawFrom = $parsed['from'];
        if (empty($rawFrom)) {
            Log::warning('FetchTicketEmailsJob: email without From header, skipping', ['subject' => $parsed['subject']]);

            return null;
        }
        $fromEmail = $this->extractEmailAddress($rawFrom);

        // Try to find by ticket number in subject (e.g., "Re: Ticket #TCK-2025-00123").
        // Only thread into the ticket when the sender matches the ticket customer,
        // otherwise a third party could inject messages into someone else's ticket.
        if (preg_match('/#(TCK-\d{4}-\d{5})/', $parsed['subject'], $matches)) {
            $ticket = Ticket::with('customer:id,email')->where('ticket_number', $matches[1])->first();
            if ($ticket && $this->senderMatchesTicket($ticket, $fromEmail)) {
                return $ticket;
            }

            if ($ticket) {
                Log::warning('FetchTicketEmailsJob: sender does not match ticket customer, creating new ticket', [
                    'ticket_number' => $matches[1],
                    'from' => $fromEmail,
                ]);
            }
        }

        // Llegados aquí no se pudo enlazar con un ticket existente, así que
        // habría que CREAR uno nuevo. Si esta conexión no permite crear tickets
        // (solo respuestas), no se crea: se devuelve null y el email se ignora.
        // Sin conexión (fallback de buzón único) se mantiene el comportamiento previo.
        if ($connection !== [] && ! ($connection['create_tickets'] ?? false)) {
            return null;
        }

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

        // Create new ticket inside a transaction so the lockForUpdate in
        // generateTicketNumber() is effective and numbers never collide.
        $ticket = DB::transaction(fn () => Ticket::create([
            'customer_id' => $customer->id,
            'subject' => $parsed['subject'],
            'description' => $parsed['body_text'] ?? $parsed['body_html'],
            'source' => 'email',
            'status_id' => TicketStatus::where('is_default', true)->first()?->id ?? 1,
            'priority' => $this->detectPriority($parsed['subject']),
            // Explícito (no depender del TicketObserver::creating): el número se
            // genera aquí, dentro de la transacción que hace efectivo el lockForUpdate.
            'ticket_number' => Ticket::generateTicketNumber(),
        ]));

        Log::info("Created new ticket #{$ticket->ticket_number} from email");

        return $ticket;
    }

    /**
     * Determine whether the sender address belongs to the ticket customer.
     */
    protected function senderMatchesTicket(Ticket $ticket, string $fromEmail): bool
    {
        $customerEmail = $ticket->customer?->email;

        if (! $customerEmail) {
            return false;
        }

        return strcasecmp(trim($customerEmail), trim($fromEmail)) === 0;
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

            $allowedExtensions = config('helpdesk.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'zip']);
            $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

            if ($extension === '' || ! in_array($extension, $allowedExtensions, true)) {
                Log::warning('FetchTicketEmailsJob: skipped attachment with disallowed extension', [
                    'filename' => $filename,
                ]);

                return null;
            }

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
            return 'urgent';
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
