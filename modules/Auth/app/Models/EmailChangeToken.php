<?php

namespace Modules\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailChangeToken extends Model
{
    protected $table = 'email_change_tokens';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'new_email',
        'token_hash',
        'expires_at',
        'confirmed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->confirmed_at === null && $this->expires_at->isFuture();
    }
}
