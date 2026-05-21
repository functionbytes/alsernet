<?php

namespace Modules\Remarketing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailer\Models\MailerTemplate;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'remarketing_campaigns';

    protected $fillable = [
        'store_id',
        'name',
        'subject',
        'preheader',
        'from_name',
        'from_email',
        'template_id',
        'mailer_template_id',
        'lang_id',
        'segment_id',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'recipients_total',
        'sent',
        'delivered',
        'bounced',
        'opened',
        'clicked',
        'unsubscribed',
        'complained',
        'revenue',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'revenue' => 'decimal:2',
            'settings' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function mailerTemplate(): BelongsTo
    {
        return $this->belongsTo(MailerTemplate::class, 'mailer_template_id');
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }
}
