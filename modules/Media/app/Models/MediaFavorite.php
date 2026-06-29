<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Media\Database\Factories\MediaFavoriteFactory;

class MediaFavorite extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'favoritable_id', 'favoritable_type'];

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): MediaFavoriteFactory
    {
        return MediaFavoriteFactory::new();
    }
}
