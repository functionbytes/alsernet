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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl whereNotifiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemUrl whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class SystemUrl extends Notification
{
    /**
     * Check if CronJob is recently executed and log a notification if not.
     */
    public static function check()
    {
        $title = trans('messages.admin.notification.system_url_title');
        self::cleanupDuplicateNotifications($title);

        $current = url('/');
        $cached = config('app.url');
        if ($current != $cached) {
            $warning = [
                'title' => $title,
                'message' => trans('messages.admin.notification.system_url_not_match', ['cached' => $cached, 'current' => $current]),
            ];

            self::warning($warning);
        }
    }
}
