<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;

class MediaShareRevocation extends Model
{
    protected $table = 'media_share_revocations';

    public $timestamps = false;

    protected $fillable = [
        'token_hash',
        'revoked_by_user_id',
        'reason',
        'revoked_at',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];
}
