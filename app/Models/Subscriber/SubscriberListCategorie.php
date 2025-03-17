<?php

namespace App\Models\Subscriber;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SubscriberListCategorie extends Model
{

    protected $table = "subscriber_list_categories";

    protected $fillable = [
        'categorie_id',
        'list_id',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\SubscriberList', 'list_id');
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\SubscriberCategorie', 'categorie_id');
    }


}
