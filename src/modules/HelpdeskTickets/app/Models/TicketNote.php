<?php

namespace Modules\HelpdeskTickets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketNote extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ticket_notes';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'title',
        'body',
        'is_pinned',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // ────────────────────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────────────────────

    /**
     * Get the ticket this note belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the agent who created this note (cross-database relationship)
     */
    public function user(): BelongsTo
    {
        $user = new User;
        $user->setConnection('mysql');

        return $this->newBelongsTo(
            $user->newQuery(),
            $this,
            'user_id',
            'id',
            'user'
        );
    }

    // ────────────────────────────────────────────────────────────────
    // Query Scopes
    // ────────────────────────────────────────────────────────────────

    /**
     * Get only pinned notes
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Get only unpinned notes
     */
    public function scopeUnpinned($query)
    {
        return $query->where('is_pinned', false);
    }

    /**
     * Filter notes by color
     */
    public function scopeByColor($query, string $color)
    {
        return $query->where('color', $color);
    }

    /**
     * Order by newest first
     */
    public function scopeRecentFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Order pinned first, then by newest
     */
    public function scopeOrderByPinnedThenRecent($query)
    {
        return $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');
    }

    // ────────────────────────────────────────────────────────────────
    // Actions
    // ────────────────────────────────────────────────────────────────

    /**
     * Pin this note to top
     */
    public function pin(): void
    {
        $this->update(['is_pinned' => true]);
    }

    /**
     * Unpin this note
     */
    public function unpin(): void
    {
        $this->update(['is_pinned' => false]);
    }

    /**
     * Change note color
     */
    public function changeColor(string $color): void
    {
        if (! in_array($color, ['yellow', 'blue', 'green', 'red', 'purple', 'orange'])) {
            throw new \InvalidArgumentException("Invalid color: {$color}");
        }

        $this->update(['color' => $color]);
    }

    /**
     * Toggle pin status
     */
    public function togglePin(): void
    {
        $this->update(['is_pinned' => ! $this->is_pinned]);
    }

    // ────────────────────────────────────────────────────────────────
    // Accessors
    // ────────────────────────────────────────────────────────────────

    /**
     * Get Bootstrap badge class for color
     */
    public function getColorBadgeAttribute(): string
    {
        return match ($this->color) {
            'yellow' => 'badge-warning',
            'blue' => 'badge-primary',
            'green' => 'badge-success',
            'red' => 'badge-danger',
            'purple' => 'badge-secondary',
            'orange' => 'badge-info',
            default => 'badge-secondary',
        };
    }

    /**
     * Get Tailwind color class
     */
    public function getTailwindColorAttribute(): string
    {
        return match ($this->color) {
            'yellow' => 'bg-yellow-100 border-yellow-400 text-yellow-900',
            'blue' => 'bg-blue-100 border-blue-400 text-blue-900',
            'green' => 'bg-green-100 border-green-400 text-green-900',
            'red' => 'bg-red-100 border-red-400 text-red-900',
            'purple' => 'bg-purple-100 border-purple-400 text-purple-900',
            'orange' => 'bg-orange-100 border-orange-400 text-orange-900',
            default => 'bg-gray-100 border-gray-400 text-gray-900',
        };
    }

    /**
     * Get CSS background color value
     */
    public function getColorHexAttribute(): string
    {
        return match ($this->color) {
            'yellow' => '#fef3c7',
            'blue' => '#dbeafe',
            'green' => '#dcfce7',
            'red' => '#fecaca',
            'purple' => '#e9d5ff',
            'orange' => '#fed7aa',
            default => '#f3f4f6',
        };
    }

    /**
     * Get human-readable summary
     */
    public function getSummaryAttribute(): string
    {
        if ($this->title) {
            return $this->title;
        }

        return substr($this->body, 0, 50).(strlen($this->body) > 50 ? '...' : '');
    }
}
