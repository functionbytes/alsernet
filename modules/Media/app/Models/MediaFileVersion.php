<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaFileVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_file_id',
        'user_id',
        'name',
        'mime_type',
        'size',
        'url',
        'disk',
        'file_hash',
        'version_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'version_number' => 'integer',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(MediaFile::class, 'media_file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
