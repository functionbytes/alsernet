<?php

namespace Modules\Core\Traits;

/**
 * Trait HasQuotaManagement
 *
 * Gestiona quotas, límites de uso y cálculos de uso para subscribers,
 * sending servers, email verification servers, dominios, etc.
 */
trait HasQuotaManagement
{
    /**
     * Get subscriber quota
     */
    public function maxSubscribers()
    {
        $count = get_tmp_quota($this, 'subscriber_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate subscribers usage percentage
     */
    public function subscribersUsage($cache = false)
    {
        $max = $this->maxSubscribers();
        $count = $this->subscribersCount($cache);

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Display subscribers usage
     */
    public function displaySubscribersUsage(): string
    {
        if ($this->maxSubscribers() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->readCache('SubscriberUsage', 0).'%';
    }

    /**
     * Get sending quota
     */
    public function maxQuota()
    {
        $quota = get_tmp_quota($this, 'sending_quota');
        if ($quota == '-1') {
            return '∞';
        } else {
            return $quota;
        }
    }

    /**
     * Get max sending server count
     */
    public function maxSendingServers()
    {
        $count = get_tmp_quota($this, 'sending_servers_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate sending servers usage
     */
    public function sendingServersUsage()
    {
        $max = $this->maxSendingServers();
        $count = $this->sendingServersCount();

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Display sending servers usage
     */
    public function displaySendingServersUsage(): string
    {
        if ($this->maxSendingServers() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->sendingServersUsage().'%';
    }

    /**
     * Get max email verification server count
     */
    public function maxEmailVerificationServers()
    {
        $count = get_tmp_quota($this, 'mail_verification_servers_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate email verification servers usage
     */
    public function emailVerificationServersUsage()
    {
        $max = $this->maxEmailVerificationServers();
        $count = $this->emailVerificationServersCount();

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Display email verification servers usage
     */
    public function displayEmailVerificationServersUsage(): string
    {
        if ($this->maxEmailVerificationServers() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->emailVerificationServersUsage().'%';
    }

    /**
     * Get max sending domains count
     */
    public function maxSendingDomains()
    {
        $count = get_tmp_quota($this, 'sending_domains_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate sending domains usage
     */
    public function sendingDomainsUsage()
    {
        $max = $this->maxSendingDomains();
        $count = $this->sendingDomainsCount();

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Get total file size usage
     */
    public function totalUploadSize(): float
    {
        return \Acelle\Library\Tool::getDirectorySize(base_path('public/source/'.$this->user->uid)) / 1048576;
    }

    /**
     * Get max upload size quota
     */
    public function maxTotalUploadSize()
    {
        $count = get_tmp_quota($this, 'max_size_upload_total');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate total upload size usage
     */
    public function totalUploadSizeUsage()
    {
        if ($this->maxTotalUploadSize() == '∞') {
            return 0;
        }
        if ($this->maxTotalUploadSize() == 0) {
            return 100;
        }

        return round((($this->totalUploadSize() / $this->maxTotalUploadSize()) * 100), 2);
    }

    /**
     * Check if user can use API
     */
    public function canUseApi(): bool
    {
        return get_tmp_quota($this, 'api_access') == 'yes';
    }

    /**
     * Color array options
     */
    public static function colors($default): array
    {
        return [
            ['value' => 'default', 'text' => trans('messages.system_default')],
            ['value' => 'blue', 'text' => trans('messages.blue')],
            ['value' => 'green', 'text' => trans('messages.green')],
            ['value' => 'brown', 'text' => trans('messages.brown')],
            ['value' => 'pink', 'text' => trans('messages.pink')],
            ['value' => 'grey', 'text' => trans('messages.grey')],
            ['value' => 'white', 'text' => trans('messages.white')],
        ];
    }

    /**
     * Items per page
     */
    public static $itemsPerPage = 25;
}
