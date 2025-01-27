<?php

namespace App\Models\Newsletter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
class NewsletterRecord extends Model
{
    use HasFactory;

    protected $table = "newsletter_lists";

    protected $fillable = [
        'old_value',
        'new_value',
        'available',
        'condition_id',
        'synced_at',
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


}
