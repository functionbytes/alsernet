<?php

/**
 * CronJobNotification class.
 *
 * Notification for cronjob issue
 *
 * LICENSE: This product components software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   Acelle Library
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Modules\Campaign\Library\Notification;

use App\Models\Notification;
use Carbon\Carbon;
use Modules\Core\Models\Setting;

/**
 * @property int $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property string $data
 * @property string|null $read_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob whereNotifiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CronJob whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CronJob extends Notification
{
    /**
     * Check if CronJob is recently executed and log a notification if not.
     */
    public static function check()
    {
        $title = 'CronJob';
        self::cleanupDuplicateNotifications($title);

        $interval = Setting::get('cronjob_min_interval');
        if (! self::isCronjobExecutedWithin($interval)) {
            $warning = [
                'title' => $title,
                'message' => trans('messages.admin.notification.cronjob_not_active', ['cronjob_min_interval' => "$interval", 'cronjob_last_executed' => self::getLastExecutionDateTime()]),
            ];

            self::warning($warning);
        }
    }

    /**
     * Check if CronJob is recently executed.
     *
     * @return bool
     */
    private static function isCronjobExecutedWithin($diff)
    {
        $timestamp = Setting::get('cronjob_last_execution');
        if (is_null($timestamp)) {
            return false;
        }

        $lastexec = Carbon::createFromTimestamp($timestamp);
        $checked = new Carbon(sprintf('%s ago', $diff));

        return $lastexec->gte($checked);
    }

    /**
     * Get last cron job executed date/time string.
     *
     * @return string
     */
    public static function getLastExecutionDateTime()
    {
        $timestamp = Setting::get('cronjob_last_execution');
        if (is_null($timestamp)) {
            return '#unknown';
        }

        return Carbon::createFromTimestamp($timestamp)->toDateTimeString();
    }
}
