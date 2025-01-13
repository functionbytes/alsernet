<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductLocation extends Model
{

    use HasFactory;

    protected $table = "product_locations";

    protected $fillable = [
        'slack',
        'product_id',
        'location_id',
        'shop_id',
        'count',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeSlack($query, $slack)
    {
        return $query->where('slack', $slack)->first();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product\Product','product_id','id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo('App\Models\Location','location_id','id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo('App\Models\Shop','shop_id','id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany('App\Models\Order\Order');
    }

}
