<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class FeedbackLog extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_feedback_logs';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'feedback_loop_handler_id',
        'email',
        'complaint_type',
        'feedback_report',
        'raw_message',
        'parsed_data',
        'subscriber_updated',
        'admin_notified',
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
            'admin_notified' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the feedback loop handler that owns this feedback log.
     */
    public function feedbackLoopHandler(): BelongsTo
    {
        return $this->belongsTo(FeedbackLoopHandler::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this complaint is for spam.
     */
    public function isSpamComplaint(): bool
    {
        return $this->complaint_type === 'spam';
    }

    /**
     * Check if this complaint is for abuse.
     */
    public function isAbuseComplaint(): bool
    {
        return $this->complaint_type === 'abuse';
    }

    /**
     * Check if this complaint is marked as not spam.
     */
    public function isNotSpam(): bool
    {
        return $this->complaint_type === 'not-spam';
    }

    /**
     * Check if this complaint is for fraud.
     */
    public function isFraudComplaint(): bool
    {
        return $this->complaint_type === 'fraud';
    }

    /**
     * Get the display label for complaint type.
     */
    public function getComplaintTypeLabel(): string
    {
        return match ($this->complaint_type) {
            'spam' => 'Spam',
            'abuse' => 'Abuse',
            'not-spam' => 'Not Spam',
            'fraud' => 'Fraud',
            default => ucfirst($this->complaint_type ?? 'Unknown'),
        };
    }
}
