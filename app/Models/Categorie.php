<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{

    protected $table = "categories";

    protected $fillable = [
        'title',
        'created_at',
        'updated_at'
    ];

    public function scopeDescending($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeAscending($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeUid($query, $uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(
            'App\Models\Subscriber\SubscriberList',
            'subscriber_list_categories',
            'categorie_id',
            'list_id'
        );
    }

    public function subscriberlists(): BelongsToMany
    {
        return $this->belongsToMany(
            'App\Models\Subscriber\SubscriberList',
            'categorie_subscriber_list',  // Pivot table name
            'categorie_id',  // Foreign key on the pivot table for this model
            'subscriber_list_id' // Foreign key on the pivot table for the related model
        );
    }


    public function listsByLang($langId)
    {
        return $this->lists()->where('subscriber_lists.lang_id', $langId);
    }


}
