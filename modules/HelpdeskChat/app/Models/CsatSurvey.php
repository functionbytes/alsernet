<?php

namespace Modules\HelpdeskChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class CsatSurvey extends Model
{
    protected $table = 'helpdesk_csat_surveys';

    protected $fillable = [
        'account_id',
        'conversation_id',
        'contact_id',
        'rating',
        'feedback',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }

    public function submitResponse(int $rating, ?string $feedback = null): void
    {
        $this->update([
            'rating' => $rating,
            'feedback' => $feedback,
            'responded_at' => now(),
        ]);
    }

    public function isResponded(): bool
    {
        return $this->responded_at !== null;
    }

    public function scopeResponded($query)
    {
        return $query->whereNotNull('responded_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('responded_at')->whereNotNull('sent_at');
    }
}
