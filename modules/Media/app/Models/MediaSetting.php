<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaSetting extends Model
{
    protected $table = 'media_settings';

    protected $fillable = [
        'key',
        'value',
        'user_id',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
