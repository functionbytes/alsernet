<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Library\StringHelper;
use Acelle\Library\ExtendedSwiftMessage;
use Acelle\Model\Campaign;
use Acelle\Model\User;
use Acelle\Model\MailList;
use Acelle\Model\Subscriber;
use Acelle\Model\CampaignTrackingLog;
use Acelle\Model\SendingServer;
use Acelle\Model\AutoTrigger;
use Acelle\Model\SendingServerElasticEmailApi;
use Acelle\Model\SendingServerElasticEmail;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TestCampaign extends Command
{

    protected $signature = 'campaign:test';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->testImap();
        return 0;
    }

    public function testSmtp()
    {
        $transport = new \Swift_SmtpTransport('smtp.elasticemail.com', 2525, 'tls');
        $transport->setUsername('');
        $transport->setPassword('');

        $mailer = new \Swift_Mailer($transport);

        $message = new ExtendedSwiftMessage('Wonderful Subject');
        $message->setFrom(array('' => 'Awsome Sender'));
        $message->setTo(array('' => 'Awsome Recipient'));
        $message->setBody('Here is the message itself');
        $mailer->send($message);
        
    }

    public function testImap()
    {
        $imapPath = "{mail.example.com:993/imap/tls}INBOX";
        $inbox = imap_open($imapPath, 'user@example.com', 'password');
        $emails = imap_search($inbox, 'UNSEEN');

        if (!empty($emails)) {
            foreach ($emails as $message) {
                var_dump($message);
            }
        }

        imap_expunge($inbox);
        imap_close($inbox);
    }
}
