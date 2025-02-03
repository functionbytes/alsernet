<?php

namespace App\Models\Subscriber;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
class SubscriberListsUser extends Model
{
    use HasFactory;

    protected $table = "subscriber_lists_users";

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
        return $this->belongsTo('App\Models\Subscriber\SubscriberList','list_id','id');
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\Subscriber','subscriber_id','id');
    }


}
