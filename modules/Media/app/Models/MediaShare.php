<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaShare extends Model
{
    protected $fillable = [
        'shareable_id',
        'shareable_type',
        'shared_by_user_id',
        'shared_with_user_id',
        'role',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
