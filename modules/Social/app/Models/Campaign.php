<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Chat\Models\Accounts\Account;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'social_campaigns';

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'color',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'boolean',
        ];
    }

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(CampaignQrCode::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === true;
    }

    public function isOngoing(): bool
    {
        $now = now();

        return $this->start_date <= $now && ($this->end_date === null || $this->end_date >= $now);
    }

    public function getTotalPosts(): int
    {
        return $this->posts()->count();
    }

    public function getPublishedPosts(): int
    {
        return $this->posts()->published()->count();
    }
}
