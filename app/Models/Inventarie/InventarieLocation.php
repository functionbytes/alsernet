<?php

namespace App\Models\Inventarie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarieLocation extends Model
{
    use HasFactory;

    protected $table = 'inventarie_locations';

    protected $fillable = [
        'slack',
        'available',
        'location_id',
        'inventarie_id',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeSlack($query ,$slack)
    {
        return $query->where('slack', $slack)->first();
    }

    public function scopeValidateExists($query, $location, $inventarie)
    {
        return $query->where('location_id', $location)
            ->where('inventarie_id', $inventarie)->exists();
    }

    public function scopeValidate($query, $location, $inventarie)
    {
        return $query->where('location_id', $location)
            ->where('inventarie_id', $inventarie)
            ->first();
    }


    public function location(): BelongsTo
    {
        return $this->belongsTo('App\Models\Location','location_id','id');
    }

    public function inventarie(): BelongsTo
    {
        return $this->belongsTo('App\Models\Inventarie\Inventarie','inventarie_id','id');
    }

    public function items()
    {
        return $this->hasMany('App\Models\Inventarie\InventarieLocationItem', 'location_id');
    }

}