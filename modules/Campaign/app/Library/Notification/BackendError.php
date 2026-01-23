<?php

/**
 * GeoIp class.
 *
 * Notification for backend issue (command)
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError whereNotifiableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError whereNotifiableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError whereReadAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BackendError whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class BackendError extends Notification
{
    //
}
