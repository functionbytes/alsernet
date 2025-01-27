<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lang extends Model
{

    use HasFactory;

    protected $table = "langs";

    protected $fillable = [
        'slack',
        'title',
        'iso_code',
        'lenguage_code',
        'locate',
        'date_format_full',
        'date_format_lite',
        'available',
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

    public function scopeIso($query, $iso)
    {
        return $query->where('iso_code', $iso)->first();
    }


    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }


}
