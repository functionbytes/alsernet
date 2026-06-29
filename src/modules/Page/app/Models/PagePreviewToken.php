<?php

namespace Modules\Page\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PagePreviewToken extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'page_preview_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'page_id',
        'token',
        'expires_at',
        'created_by',
        'viewed_count',
        'last_viewed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'viewed_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the page that owns the token.
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the user who created the token.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include active (non-expired) tokens.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired tokens.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to order by most recently created.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token is still active.
     */
    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Increment the viewed count and update last viewed timestamp.
     */
    public function recordView(): void
    {
        $this->increment('viewed_count');
        $this->update(['last_viewed_at' => now()]);
    }

    /**
     * Generate a secure unique token.
     */
    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Get the preview URL for this token.
     */
    public function getPreviewUrl(): string
    {
        return route('page.preview', [
            'slug' => $this->page->slug,
            'token' => $this->token,
        ]);
    }

    /**
     * Get human readable time until expiration.
     */
    public function getExpiresInHuman(): string
    {
        if ($this->isExpired()) {
            return 'Expirado';
        }

        return $this->expires_at->diffForHumans();
    }
}
