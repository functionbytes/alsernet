<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Engagement\Database\Factories\SegmentFactory;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;

class Segment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_segments';

    protected $fillable = [
        'inbox_id',
        'name',
        'description',
        'conditions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'engagement_segment_customers', 'segment_id', 'customer_id')
            ->withPivot('matched_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }

    protected static function newFactory(): SegmentFactory
    {
        return SegmentFactory::new();
    }
}
