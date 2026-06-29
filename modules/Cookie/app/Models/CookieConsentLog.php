<?php

namespace Modules\Cookie\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CookieConsentLog extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'ip_hash',
        'action',
        'accepted_categories',
        'user_agent',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'accepted_categories' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
