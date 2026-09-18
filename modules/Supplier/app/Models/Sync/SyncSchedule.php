<?php

namespace Modules\Supplier\Models\Sync;

use App\Traits\HasUid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Supplier\Database\Factories\Sync\SyncScheduleFactory;
use Modules\Supplier\Models\Sync\SyncBatch;

/**
 * Represents a scheduled synchronization job configuration.
 *
 * @property int $id
 * @property string $uid ULID unique identifier
 * @property string $sync_type Type of sync: 'model', 'product'
 * @property string $label Human-readable label
 * @property int $hour Hour of the day when sync runs (0-23)
 * @property int $minute Minute of the hour when sync runs (0-59)
 * @property bool $is_enabled Whether the schedule is active
 * @property Carbon|null $last_run_at When the schedule last ran
 * @property string|null $last_run_status Result of last run: 'success', 'failed', 'running'
 * @property int|null $last_batch_id ID of the last associated sync batch
 * @property array|null $metadata Additional metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SyncSchedule extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_sync_schedules';

    protected $fillable = [
        'uid',
        'sync_type',
        'label',
        'hour',
        'minute',
        'is_enabled',
        'last_run_at',
        'last_run_status',
        'last_batch_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'hour' => 'integer',
            'minute' => 'integer',
            'is_enabled' => 'boolean',
            'last_run_at' => 'datetime',
            'last_batch_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function newFactory(): SyncScheduleFactory
    {
        return SyncScheduleFactory::new();
    }

    public function getFormattedTimeAttribute(): string
    {
        return sprintf('%02d:%02d', $this->hour, $this->minute);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('sync_type', $type);
    }

    public function lastBatch(): BelongsTo
    {
        return $this->belongsTo(SyncBatch::class, 'last_batch_id');
    }
}
