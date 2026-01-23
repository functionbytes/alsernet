<?php

namespace Modules\Subscriber\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class SubscriberCondition extends Model
{
    use HasUid;

    protected $table = 'subscriber_conditions';

    protected $fillable = [
        'uid',
        'title',
        'slug',
        'reference',
        'barcode',
        'stock',
        'available',
        'created_at',
        'updated_at',
    ];

    public function scopeId($query, $id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }
}
