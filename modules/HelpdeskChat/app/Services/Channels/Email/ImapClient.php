<?php

namespace Modules\HelpdeskChat\Services\Channels\Email;

use App\Models\Channels\Email;
use Illuminate\Support\Facades\Log;

class ImapClient
{
    protected $connection;

    protected Email $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    /**
     * Connect to IMAP server.
     */
    public function connect(): bool
    {
        try {
            if (! $this->email->imap_enabled) {
                throw new \Exception('IMAP is not enabled for this email channel');
            }

            $mailbox = $this->buildMailboxString();

            $this->connection = @imap_open(
                $mailbox,
                $this->email->imap_login,
                $this->email->imap_password,
                OP_READONLY
            );

            if (! $this->connection) {
                $error = imap_last_error();
                throw new \Exception("IMAP connection failed: {$error}");
            }

            return true;
        } catch (\Exception $e) {
            Log::error('IMAP connection error', [
                'email_id' => $this->email->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Fetch unread emails.
     */
    public function fetchUnreadEmails(int $limit = 20): array
    {
        if (! $this->connection) {
            if (! $this->connect()) {
                return [];
            }
        }

        try {
            // Search for unseen messages
            $unseenMessages = imap_search($this->connection, 'UNSEEN', SE_UID);

            if (! $unseenMessages) {
                return [];
            }

            // Limit the number of emails to fetch
            $unseenMessages = array_slice($unseenMessages, 0, $limit);

            $emails = [];

            foreach ($unseenMessages as $uid) {
                $email = $this->fetchEmail($uid);
                if ($email) {
                    $emails[] = $email;
                }
            }

            return $emails;
        } catch (\Exception $e) {
            Log::error('Error fetching emails', [
                'email_id' => $this->email->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch a single email by UID.
     */
    protected function fetchEmail(int $uid): ?array
    {
        try {
            // Get message number from UID
            $msgno = imap_msgno($this->connection, $uid);

            if (! $msgno) {
                return null;
            }

            // Fetch headers
            $header = imap_headerinfo($this->connection, $msgno);
            $structure = imap_fetchstructure($this->connection, $msgno);

            // Get email body
            $body = $this->getBody($msgno, $structure);

            // Get message ID for threading
            $messageId = $header->message_id ?? null;
            $inReplyTo = $header->in_reply_to ?? null;
            $references = $header->references ?? null;

            // Parse from address
            $from = $header->from[0] ?? null;
            $fromEmail = $from ? strtolower($from->mailbox.'@'.$from->host) : null;
            $fromName = $from->personal ?? $fromEmail;

            // Parse subject
            $subject = $header->subject ?? '(No Subject)';
            $subject = $this->decodeHeader($subject);

            return [
                'uid' => $uid,
                'message_id' => $messageId,
                'in_reply_to' => $inReplyTo,
                'references' => $references,
                'from_email' => $fromEmail,
                'from_name' => $this->decodeHeader($fromName),
                'to_email' => $this->email->email,
                'subject' => $subject,
                'body' => $body,
                'date' => $header->date ?? now()->toDateTimeString(),
                'is_html' => $structure->subtype === 'HTML',
            ];
        } catch (\Exception $e) {
            Log::error('Error parsing email', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get email body.
     */
    protected function getBody(int $msgno, $structure): string
    {
        $body = '';

        // Check if email has parts (multipart)
        if (isset($structure->parts) && count($structure->parts)) {
            // Multipart email
            foreach ($structure->parts as $partNum => $part) {
                // Check if this is the text/plain or text/html part
                if ($part->subtype === 'PLAIN' || $part->subtype === 'HTML') {
                    $body = imap_fetchbody($this->connection, $msgno, $partNum + 1);

                    // Decode based on encoding
                    if ($part->encoding == 3) { // BASE64
                        $body = base64_decode($body);
                    } elseif ($part->encoding == 4) { // QUOTED-PRINTABLE
                        $body = quoted_printable_decode($body);
                    }

                    // Prefer HTML over plain text
                    if ($part->subtype === 'HTML') {
                        break;
                    }
                }
            }
        } else {
            // Simple email (not multipart)
            $body = imap_body($this->connection, $msgno);

            if ($structure->encoding == 3) { // BASE64
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) { // QUOTED-PRINTABLE
                $body = quoted_printable_decode($body);
            }
        }

        return $body;
    }

    /**
     * Decode MIME header.
     */
    protected function decodeHeader(string $header): string
    {
        $decoded = imap_mime_header_decode($header);
        $result = '';

        foreach ($decoded as $part) {
            $result .= $part->text;
        }

        return $result;
    }

    /**
     * Mark email as read.
     */
    public function markAsRead(int $uid): bool
    {
        try {
            return imap_setflag_full($this->connection, $uid, '\\Seen', ST_UID);
        } catch (\Exception $e) {
            Log::error('Error marking email as read', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Build IMAP mailbox connection string.
     */
    protected function buildMailboxString(): string
    {
        $ssl = $this->email->imap_enable_ssl ? '/ssl' : '';
        $novalidate = '/novalidate-cert'; // For development

        return sprintf(
            '{%s:%d/imap%s%s}INBOX',
            $this->email->imap_address,
            $this->email->imap_port,
            $ssl,
            $novalidate
        );
    }

    /**
     * Close IMAP connection.
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            imap_close($this->connection);
            $this->connection = null;
        }
    }

    /**
     * Destructor to ensure connection is closed.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
