<?php

namespace App\Models\Subscriber;

use App\Jobs\SyncSuscriberListJob;
use App\Jobs\RemoveSuscriberListJob;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class   SubscriberCategorie extends Model
{

    protected $table = "subscriber_categories";

    protected $fillable = [
        'category_id',
        'subscriber_id',
        'created_at',
        'updated_at'
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($subscriberCategorie) {
            dispatch_sync(function () use ($subscriberCategorie) {
                dd($subscriberCategorie);
            });
        });

        static::deleted(function ($subscriberCategorie) {
            dispatch_sync(function () use ($subscriberCategorie) {
                dd($subscriberCategorie);
            });
        });

//
//
//        static::created(function ($subscriberCategorie) {
//
//            Log::info("CREATED:", ['subscriberCategorie' => $subscriberCategorie->toArray()]);
//
////            $subscriber = $subscriberCategorie->subscriber;
////            $categoryId = $subscriberCategorie->category_id;
////
////            $mailingListIds = $subscriberCategorie->lists()->pluck('id')->toArray();
////
////            if (!empty($mailingListIds)) {
////                AddSuscriberListJob::dispatch($subscriber->id, [$categoryId]);
////            }
//        });
//
//        static::deleting(function ($subscriberCategorie) {
//
//            Log::info("DELETED:", ['subscriberCategorie' => $subscriberCategorie->toArray()]);
////
////            $subscriber = $subscriberCategorie->subscriber;
////            $categoryId = $subscriberCategorie->category_id;
////
////            $mailingListIds = $subscriberCategorie->lists()->pluck('id')->toArray();
////
////            if (!empty($mailingListIds)) {
////                RemoveSuscriberListJob::dispatch($subscriber->id, [$categoryId]);
////            }
//        });
    }


    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo('App\Models\Category', 'category_id', 'id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo('App\Models\Subscriber\Subscriber','subscriber_id','id');
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

}
