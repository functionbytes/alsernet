<?php

namespace App\Models\Subscriber;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class SubscriberListUser extends Model
{

    protected $table = "subscriber_list_users";

    protected $fillable = [
        'list_id',
        'subscriber_id',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\SubscriberList', 'list_id', 'id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\Subscriber', 'subscriber_id', 'id');
    }

}
