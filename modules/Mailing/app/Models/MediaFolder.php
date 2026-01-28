<?php

namespace Modules\Mailing\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaFolder extends Model
{
    use HasFactory;

    protected $table = 'mails_media_folders';

    protected $fillable = [
        'name',
    ];

    public function mediaFiles()
    {
        return $this->hasMany(MediaFile::class);
    }
}
