<?php

namespace App\Library\SendingServer;

interface DomainVerificationInterface
{
    /*
        return [
            'identity' => [
                'name' => '_amazonses.acellemail.live',
                'type' => 'CNAME',
                'value' => 'IBasT4ddDMUsPcd3hchW9b7uQa01xFDorqkbELxXg+o=',
            ],
            'dkims' => [
                [ 'name' => '3hsorb._domainkey.acellemail.live', 'type' => 'CNAME', 'value' => '3hsorb.dkim.amazonses.com' ],
                [ 'name' => 'rjmo7u._domainkey.acellemail.live', 'type' => 'CNAME', 'value' => 'rjmo7u.dkim.amazonses.com' ],
                [ 'name' => 'cjaydq._domainkey.acellemail.live', 'type' => 'CNAME', 'value' => 'cjaydq.dkim.amazonses.com' ],
            ]

        ]
    */
    public function verifyDomain($domain): array;

    /*
     * Return: an array of boolean value indicating whether or not the related check is true or false
     * Sample: [ bool $identity, bool $dkim, bool $spf, bool $finalStatus ]
     */
    public function checkDomainVerificationStatus($domain): array;
}
