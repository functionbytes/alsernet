<?php

namespace Modules\Campaign\Console\Commands;

use Acelle\Library\ExtendedSwiftMessage;
use Illuminate\Console\Command;

class TestCampaign extends Command
{
    protected $signature = 'campaign:test';

    protected $description = 'Test campaign SMTP and IMAP connections';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->testImap();

        return 0;
    }

    public function testSmtp(): void
    {
        $transport = new \Swift_SmtpTransport('smtp.elasticemail.com', 2525, 'tls');
        $transport->setUsername('');
        $transport->setPassword('');

        $mailer = new \Swift_Mailer($transport);

        $message = new ExtendedSwiftMessage('Wonderful Subject');
        $message->setFrom(['' => 'Awsome Sender']);
        $message->setTo(['' => 'Awsome Recipient']);
        $message->setBody('Here is the message itself');
        $mailer->send($message);
    }

    public function testImap(): void
    {
        $imapPath = '{mail.example.com:993/imap/tls}INBOX';
        $inbox = imap_open($imapPath, 'user@example.com', 'password');
        $emails = imap_search($inbox, 'UNSEEN');

        if (! empty($emails)) {
            foreach ($emails as $message) {
                var_dump($message);
            }
        }

        imap_expunge($inbox);
        imap_close($inbox);
    }
}
