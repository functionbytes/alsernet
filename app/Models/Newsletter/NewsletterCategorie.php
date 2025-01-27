<?php

namespace App\Models\Newsletter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Models\Kardex;

class NewsletterCategorie extends Model
{
    use HasFactory;

    protected $table = "newsletter_categories";

    protected $fillable = [
        'category_id',
        'newsletter_id',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }


    public function category(): BelongsTo
    {
        return $this->belongsTo('App\Models\Newsletter\NewsletterCategorie','category_id','id');
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo('App\Models\Newsletter\Newsletter','newsletter_id','id');
    }

}
