<?php

namespace Modules\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FormSubmissionFile extends Model
{
    protected $table = 'form_submission_files';

    protected $fillable = ['submission_id', 'uploaded_by', 'filename', 'path', 'mime_type', 'size'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    public function getSizeFormattedAttribute(): string
    {
        if ($this->size < 1024) {
            return $this->size.' B';
        }

        if ($this->size < 1048576) {
            return round($this->size / 1024, 2).' KB';
        }

        return round($this->size / 1048576, 2).' MB';
    }
}
