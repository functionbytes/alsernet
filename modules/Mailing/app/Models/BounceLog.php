<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class BounceLog extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_bounce_logs';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'bounce_handler_id',
        'email',
        'bounce_type',
        'reason',
        'raw_message',
        'parsed_data',
        'subscriber_updated',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'parsed_data' => 'json',
            'subscriber_updated' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the bounce handler that owns this bounce log.
     */
    public function bounceHandler(): BelongsTo
    {
        return $this->belongsTo(BounceHandler::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is a hard bounce.
     */
    public function isHardBounce(): bool
    {
        return $this->bounce_type === 'hard';
    }

    /**
     * Check if this is a soft bounce.
     */
    public function isSoftBounce(): bool
    {
        return $this->bounce_type === 'soft';
    }

    /**
     * Check if this is a complaint.
     */
    public function isComplaint(): bool
    {
        return $this->bounce_type === 'complaint';
    }

    /**
     * Get the display label for bounce type.
     */
    public function getBounceTypeLabel(): string
    {
        return match ($this->bounce_type) {
            'hard' => 'Hard Bounce',
            'soft' => 'Soft Bounce',
            'complaint' => 'Complaint',
            'unknown' => 'Unknown',
            default => ucfirst($this->bounce_type),
        };
    }
}
