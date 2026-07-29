<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $uid
 * @property string $title
 * @property string $iso_code
 * @property string $call_prefix
 * @property int $available
 * @property int $currency_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie ascending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie descending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereCallPrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereCurrencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereIsoCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Countrie whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Countrie extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'title',
        'created_at',
        'updated_at',
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}
