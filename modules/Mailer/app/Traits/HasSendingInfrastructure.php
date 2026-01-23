<?php

namespace Modules\Mailer\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\Setting;
use Modules\Subscriber\Jobs\ImportBlacklistJob;

/**
 * Trait HasSendingInfrastructure
 *
 * Gestiona la infraestructura de envío de emails: servidores de envío,
 * dominios, verificación de emails, senders, tracking domains, etc.
 */
trait HasSendingInfrastructure
{
    /**
     * Get sending servers
     */
    public function sendingServers(): HasMany
    {
        return $this->hasMany('App\Models\SendingServer');
    }

    /**
     * Get active sending servers
     */
    public function activeSendingServers()
    {
        return $this->sendingServers()->where('status', '=', \App\Models\SendingServer::STATUS_ACTIVE);
    }

    /**
     * Get sending domains
     */
    public function sendingDomains(): HasMany
    {
        return $this->hasMany('App\Models\SendingDomain');
    }

    /**
     * Get email verification servers
     */
    public function emailVerificationServers(): HasMany
    {
        return $this->hasMany('App\Models\EmailVerificationServer');
    }

    /**
     * Get active email verification servers
     */
    public function activeEmailVerificationServers()
    {
        return $this->emailVerificationServers()
            ->where('status', '=', \App\Models\EmailVerificationServer::STATUS_ACTIVE);
    }

    /**
     * Get senders (direct children only)
     */
    public function senders(): HasMany
    {
        return $this->hasMany('App\Models\Sender');
    }

    /**
     * Get tracking domains
     */
    public function trackingDomains(): HasMany
    {
        return $this->hasMany('App\Models\TrackingDomain');
    }

    /**
     * Get sub accounts
     */
    public function subAccounts(): HasMany
    {
        return $this->hasMany('App\Models\SubAccount');
    }

    /**
     * Count sending servers
     */
    public function sendingServersCount(): int
    {
        return $this->sendingServers()->count();
    }

    /**
     * Count sending domains
     */
    public function sendingDomainsCount(): int
    {
        return $this->sendingDomains()->count();
    }

    /**
     * Count email verification servers
     */
    public function emailVerificationServersCount(): int
    {
        return $this->emailVerificationServers()->count();
    }

    /**
     * Get customer's sending server types
     */
    public function getSendingServertypes(): array
    {
        $allTypes = \App\Models\SendingServer::types();
        $types = [];

        foreach ($allTypes as $type => $server) {
            if ($this->isAllowCreateSendingServerType($type)) {
                $types[$type] = $server;
            }
        }

        if (! Setting::isYes('delivery.sendmail')) {
            unset($types['sendmail']);
        }

        if (! Setting::isYes('delivery.phpmail')) {
            unset($types['php-mail']);
        }

        return $types;
    }

    /**
     * Check if customer can create sending server type
     */
    public function isAllowCreateSendingServerType($type): bool
    {
        $customerTypes = get_tmp_quota($this, 'sending_server_types');
        if (get_tmp_quota($this, 'all_sending_server_types') == 'yes' ||
            (isset($customerTypes[$type]) && $customerTypes[$type] == 'yes')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get list of available email verification servers
     */
    public function getEmailVerificationServers()
    {
        if (! config('app.saas')) {
            return \App\Models\EmailVerificationServer::getAllAdminActive()->get()->map(function ($server) {
                return $server;
            });
        } elseif ($this->getCurrentActiveGeneralSubscription()->planGeneral->useOwnEmailVerificationServer()) {
            return $this->activeEmailVerificationServers()->get()->map(function ($server) {
                return $server;
            });
        } else {
            return $this->getCurrentActiveGeneralSubscription()->planGeneral->getEmailVerificationServers();
        }
    }

    /**
     * Get email verification server select options
     */
    public function emailVerificationServerSelectOptions(): array
    {
        $servers = $this->getEmailVerificationServers();
        $options = [];
        foreach ($servers as $server) {
            $options[] = ['text' => $server->name, 'value' => $server->uid];
        }

        return $options;
    }

    /**
     * Get verified identities droplist
     */
    public function verifiedIdentitiesDroplist($keyword = null): array
    {
        $droplist = [];
        $topList = [];
        $bottomList = [];

        if (! $keyword) {
            $keyword = '###';
        }

        foreach ($this->getVerifiedIdentities() as $item) {
            // check if email
            if (extract_email($item) !== null) {
                $email = extract_email($item);
                if (strpos(strtolower($email), $keyword) === 0) {
                    $topList[] = [
                        'text' => extract_name($item),
                        'value' => $email,
                        'desc' => str_replace($keyword, '<span class="text-semibold text-primary"><strong>'.$keyword.'</strong></span>', $email),
                    ];
                } else {
                    $bottomList[] = [
                        'text' => extract_name($item),
                        'value' => $email,
                        'desc' => $email,
                    ];
                }
            } else { // domains
                $dKey = explode('@', $keyword);
                $eKey = $dKey[0];
                $dKey = isset($dKey[1]) ? $dKey[1] : null;
                if ($keyword == '###') {
                    $eKey = '****';
                }
                $topList[] = [
                    'text' => $eKey.'@'.str_replace($dKey, '<span class="text-semibold text-primary"><strong>'.$dKey.'</strong></span>', $item),
                    'subfix' => $item,
                    'desc' => null,
                ];
            }
        }

        $droplist = array_merge($topList, $bottomList);

        return $droplist;
    }

    /**
     * Allow unverified from email address
     */
    public function allowUnverifiedFromEmailAddress(): bool
    {
        $server = null;
        if (! config('app.saas')) {
            $server = get_tmp_primary_server();
        } else {
            $plan = $this->getNewOrActiveGeneralSubscription()->planGeneral;

            if ($plan->useSystemSendingServer()) {
                $server = $plan->primarySendingServer();
            }
        }

        return (is_null($server)) ? true : $server->allowUnverifiedFromEmailAddress();
    }

    /**
     * Get verified tracking domain options
     */
    public function getVerifiedTrackingDomainOptions()
    {
        return $this->trackingDomains()->verified()->get()->map(function ($domain) {
            return ['value' => $domain->uid, 'text' => $domain->name];
        });
    }

    /**
     * Check if allows verifying own domains
     */
    public function allowVerifyingOwnDomains(): bool
    {
        if (config('app.saas')) {
            $subscription = $this->getCurrentActiveGeneralSubscription();
            $server = get_tmp_primary_server();
        } else {
            $server = get_tmp_primary_server();
        }

        if ($server && ($server->allowVerifyingOwnDomains() || $server->allowVerifyingOwnDomainsRemotely())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get verified identities
     */
    public function getVerifiedIdentities(): array
    {
        $list = [];

        // own emails
        if (! config('app.saas') || $this->getCurrentActiveGeneralSubscription()->planGeneral->allowSenderVerification()) {
            $senders = $this->senders()->verified()->get();
            foreach ($senders as $sender) {
                $list[] = $sender->name.' <'.$sender->email.'>';
            }
        }

        // own domain
        $domains = $this->sendingDomains()->active();

        if (config('app.saas')) {
            $plan = $this->getCurrentActiveGeneralSubscription()->planGeneral;
            if ($plan->useSystemSendingServer()) {
                $server = $plan->primarySendingServer();
                if (! $server->allowOtherSendingDomains()) {
                    $domains->bySendingServer($server);
                }
            }
        }

        foreach ($domains->get() as $domain) {
            $list[] = $domain->name;
        }

        if (config('app.saas')) {
            // plan sending server emails if system sending servers
            if ($plan->useSystemSendingServer()) {
                $list = array_merge($plan->getVerifiedIdentities(), $list);
            }
        }

        return array_values(array_unique($list));
    }

    /**
     * Import blacklist jobs
     */
    public function importBlacklistJobs()
    {
        return $this->jobMonitors()
            ->orderBy('job_monitors.id', 'DESC')
            ->where('job_type', ImportBlacklistJob::class);
    }
}
