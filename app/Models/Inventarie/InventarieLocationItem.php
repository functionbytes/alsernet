<?php

namespace App\Models\Inventarie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarieLocationItem extends Model
{
    use HasFactory;

    protected $table = 'inventarie_locations_items';

    protected $fillable = [
        'uid',
        'count',
        'product_id',
        'location_id',
        'original_id',
        'validate_id',
        'condition_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo('App\Models\Product\Product','product_id','id');
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User','user_id','id');
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo('App\Models\Location','original_id','id');
    }

    public function validate(): BelongsTo
    {
        return $this->belongsTo('App\Models\Location','validate_id','id');
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo('App\Models\Inventarie\InventarieCondition','condition_id','id');
    }

    public function location()
    {
        return $this->belongsTo('App\Models\Inventarie\InventarieLocation', 'location_id');
    }

}
