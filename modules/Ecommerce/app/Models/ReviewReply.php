<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReply extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_review_replies';

    protected $fillable = [
        'review_id',
        'user_id',
        'comment',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class, 'review_id');
    }
}
