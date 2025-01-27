<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class ChatComment extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'ticket_comments';

    protected $fillable = [
        'ticket_id', 
        'user_id', 
        'comment', 
        'image',
        'cust_id',
        'display'
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo('App\Models\Ticket');
    }

    public function cust(): BelongsTo
    {
        return $this->belongsTo('App\Models\User');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('comments');
    }

}
