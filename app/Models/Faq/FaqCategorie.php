<?php

namespace App\Models\Faq;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class FaqCategorie extends Model
{
    use HasFactory;

    protected $table = 'faq_categories';

    protected $fillable = [
        'slack',
        'title',
        'slug',
        'available',
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
    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeSlug($query ,$slug)
    {
        return $query->where('slug', $slug)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany('App\Models\Faq\Instruction','categorie_id');
    }

}
