<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{

    use HasFactory;

    protected $table = "locations";

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

    public function scopeTitle($query, $location, $shopId)
    {
        return $query->where('title', $location)->first();
    }

    public function scopeValidate($query, $location, $shopId)
    {
        return $query->where('title', $location)
            ->where('shop_id', $shopId)
            ->first();
    }


    public function scopeValidateExits($query, $location, $shopId)
    {
        return $query->where('title', $location)
            ->where('shop_id', $shopId)
            ->exists();
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo('App\Models\Location');
    }


    public function orders(): HasMany
    {
        return $this->hasMany('App\Models\Order\Order');
    }


}