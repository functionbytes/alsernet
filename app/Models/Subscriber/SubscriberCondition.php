<?php

namespace App\Models\Subscriber;

use Illuminate\Database\Eloquent\Model;
use App\Library\Traits\HasUid;

class SubscriberCondition extends Model
{

    use HasUid;

    protected $table = "subscriber_conditions";

    protected $fillable = [
        'uid',
        'title',
        'slug',
        'reference',
        'barcode',
        'stock',
        'available',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
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
