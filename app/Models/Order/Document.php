<?php

namespace App\Models\Order;

use App\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model  implements HasMedia
{
    use HasFactory ,HasUid ,  InteractsWithMedia;

    protected $table = "request_documents";

    protected $fillable = [
        'uid',
        'type',
        'proccess',
        'label',
        'order_id',
        'customer_id',
        'cart_id',
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
    public function scopeOrder($query ,$order)
    {
        return $query->where('order_id' ,$order)->first();
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id' ,$id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function getAllDocumentsUrls(): array
    {
        return $this->getMedia('documents')->map(function ($media) {
            return $media->getUrl();
        })->toArray();
    }
    public function getAllDocuments(): array
    {
        return $this->getMedia('documents')->toArray();
    }


    public function getDocumentUrl(): ?string
    {
        $media = $this->getFirstMedia('documents');
        return $media ? $media->getUrl() : null;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Order\Order','order_id','id_order');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Customer', 'customer_id', 'id_customer');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo('App\Models\Prestashop\Cart\Cart','cart_id','id_cart');
    }

}
