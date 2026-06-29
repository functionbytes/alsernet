<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Mailrelay\Enums\ImportStatus;

class ImportJob extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'mails_import_jobs';

    protected $fillable = [
        'filename',
        'status',
        'total_emails',
        'processed_emails',
        'valid_emails',
        'invalid_emails',
        'report',
        'options',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => ImportStatus::class,
        'report' => 'array',
        'options' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Scope to get completed import jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', ImportStatus::COMPLETED);
    }

    /**
     * Scope to get processing import jobs
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', ImportStatus::PROCESSING);
    }

    /**
     * Mark the import job as processing and set started_at timestamp
     */
    public function start(): void
    {
        $this->update([
            'status' => ImportStatus::PROCESSING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the import job as completed and set completed_at timestamp
     */
    public function complete(): void
    {
        $this->update([
            'status' => ImportStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the import job as failed and record the reason
     */
    public function fail(string $reason): void
    {
        $this->update([
            'status' => ImportStatus::FAILED,
            'completed_at' => now(),
            'report' => array_merge($this->report ?? [], ['error' => $reason]),
        ]);
    }

    /**
     * Increment processed emails counter and update valid/invalid counters
     */
    public function incrementProcessed(bool $isValid): void
    {
        $this->increment('processed_emails');

        if ($isValid) {
            $this->increment('valid_emails');
        } else {
            $this->increment('invalid_emails');
        }
    }

    /**
     * Calculate and return the progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_emails === 0) {
            return 0;
        }

        return round(($this->processed_emails / $this->total_emails) * 100, 2);
    }
}
