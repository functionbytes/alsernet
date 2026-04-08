<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewTemplateVersion extends Model
{
    protected $fillable = [
        'review_reply_template_id',
        'content',
        'language',
        'version_number',
        'created_by',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReviewReplyTemplate::class, 'review_reply_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
