<?php

namespace Modules\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormAccessToken extends Model
{
    protected $table = 'form_access_tokens';

    protected $fillable = [
        'form_id',
        'token',
        'email',
        'max_uses',
        'times_used',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'times_used' => 'integer',
            'max_uses' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeValid($query)
    {
        return $query->whereColumn('times_used', '<', 'max_uses')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function isValid(): bool
    {
        if ($this->times_used >= $this->max_uses) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function markUsed(): void
    {
        $this->increment('times_used');
    }
}
