<?php

namespace Modules\Mailing\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    use HasFactory;

    protected $table = 'mails_media_files';

    protected $fillable = [
        'filename',
        'file_url',
        'folder_id',
    ];

    public function folder()
    {
        return $this->belongsTo(MediaFolder::class);
    }
}
