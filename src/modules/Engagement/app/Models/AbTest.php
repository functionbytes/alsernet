<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Engagement\Database\Factories\AbTestFactory;
use Modules\Helpdesk\Models\Inbox;

class AbTest extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_RUNNING = 'running';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    protected $connection = 'helpdesk';

    protected $table = 'engagement_ab_tests';

    protected $fillable = [
        'inbox_id',
        'name',
        'description',
        'status',
        'sample_size',
        'started_at',
        'ended_at',
        'winner_variant_id',
    ];

    protected function casts(): array
    {
        return [
            'sample_size' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(AbTestVariant::class, 'ab_test_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopeForInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }

    protected static function newFactory(): AbTestFactory
    {
        return AbTestFactory::new();
    }
}
