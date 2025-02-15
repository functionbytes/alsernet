<?php

namespace App\Models\Inventarie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class InventarieCondition extends Model
{

    use HasFactory;

    protected $table = "inventarie_conditions";

    protected $fillable = [
        'uid',
        'title',
        'slug',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query, $uid)
{
        return $query->where('uid', $uid)->first();
}

    public function scopeSlug($query ,$slug)
    {
        return $query->where('slug', $slug)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function items(): HasMany
    {
        return $this->hasMany('App\Models\Inventarie\InventarieLocationItems');
    }

}
