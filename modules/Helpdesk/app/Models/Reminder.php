<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Database\Factories\ReminderFactory;

class Reminder extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_reminders';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'title',
        'notes',
        'remind_at',
        'email_notify',
        'notified_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'notified_at' => 'datetime',
            'completed_at' => 'datetime',
            'email_notify' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    protected static function newFactory(): ReminderFactory
    {
        return new ReminderFactory;
    }
}
