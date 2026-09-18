<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class MediaTag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'color', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): MorphToMany
    {
        return $this->morphedByMany(MediaFile::class, 'taggable', 'media_taggables');
    }

    public function folders(): MorphToMany
    {
        return $this->morphedByMany(MediaFolder::class, 'taggable', 'media_taggables');
    }
}
