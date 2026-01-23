<?php

namespace Modules\HelpdeskChat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Teams\Team;

class AgentPerformanceSnapshot extends Model
{
    protected $table = 'helpdesk_agent_performance_snapshots';

    protected $fillable = [
        'account_id',
        'user_id',
        'team_id',
        'snapshot_date',
        'snapshot_type',
        'snapshot_hour',
        'metrics',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'metrics' => 'array',
            'snapshot_hour' => 'integer',
        ];
    }

    /**
     * Get the account that owns the snapshot.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user (agent) this snapshot belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team this snapshot belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope to filter snapshots for a specific agent.
     */
    public function scopeForAgent($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter snapshots for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to filter snapshots by date range.
     */
    public function scopeForDateRange($query, $start, $end)
    {
        return $query->whereBetween('snapshot_date', [$start, $end]);
    }

    /**
     * Scope to get only daily snapshots.
     */
    public function scopeDaily($query)
    {
        return $query->where('snapshot_type', 'daily');
    }

    /**
     * Scope to get only hourly snapshots.
     */
    public function scopeHourly($query)
    {
        return $query->where('snapshot_type', 'hourly');
    }

    /**
     * Get a specific metric value.
     */
    public function getMetric(string $key, mixed $default = null): mixed
    {
        return data_get($this->metrics, $key, $default);
    }

    /**
     * Set a specific metric value.
     */
    public function setMetric(string $key, mixed $value): void
    {
        $metrics = $this->metrics ?? [];
        data_set($metrics, $key, $value);
        $this->metrics = $metrics;
    }
}
