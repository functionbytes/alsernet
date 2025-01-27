<?php

namespace App\Models\Newsletter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
class NewsletterJob extends Model
{
    use HasFactory;

    protected $table = "newsletters";

    protected $fillable = [
        'condition_id',
        'newsletter_id',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }


    public function condition(): BelongsTo
    {
        return $this->belongsTo('App\Models\Newsletter\NewsletterCondition','condition_id','id');
    }

    public function newsletter(): BelongsTo
    {
        return $this->belongsTo('App\Models\Newsletter\Newsletter','newsletter_id','id');
    }


}
