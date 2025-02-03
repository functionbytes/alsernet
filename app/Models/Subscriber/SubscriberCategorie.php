<?php

namespace App\Models\Subscriber;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Models\Kardex;

class SubscriberCategorie extends Model
{
    use HasFactory;

    protected $table = "subscriber_categories";

    protected $fillable = [
        'category_id',
        'subscriber_id',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\SubscriberCategorie','category_id','id');
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\Subscriber','subscriber_id','id');
    }

}
