<?php

namespace Modules\Media\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderTemplate extends Model
{
    protected $table = 'media_folder_templates';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'structure',
        'user_id',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'structure' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
