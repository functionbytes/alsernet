<?php

namespace Modules\HelpdeskChat\Models\Contacts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContactAvatar extends Model
{
    protected $fillable = [
        'contact_id',
        'file_path',
        'thumbnail_path',
        'file_size',
        'mime_type',
    ];

    /**
     * Get the contact that owns the avatar.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the full URL of the avatar.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the full URL of the thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null;
    }

    /**
     * Delete the avatar files from storage.
     */
    public function deleteFiles(): void
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }

        if ($this->thumbnail_path && Storage::exists($this->thumbnail_path)) {
            Storage::delete($this->thumbnail_path);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($avatar) {
            $avatar->deleteFiles();
        });
    }
}
