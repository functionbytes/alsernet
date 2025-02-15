<?php

namespace App\Models\Inventarie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Inventarie extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

	protected $table = 'inventaries';

    protected static $recordEvents = ['deleted','updated','created'];

    protected $fillable = [
        'uid',
        'available',
        'shop_id',
        'created_at',
        'closet_at',
        'updated_at'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty() ->logFillable() ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo('App\Models\Shop','shop_id','id');
    }

    public function locations()
    {
        return $this->hasMany('App\Models\Inventarie\InventarieLocation');
    }


}

