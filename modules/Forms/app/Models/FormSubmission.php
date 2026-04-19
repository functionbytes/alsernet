<?php

namespace Modules\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Forms\Database\Factories\FormSubmissionFactory;

class FormSubmission extends Model
{
    use HasFactory;

    protected $table = 'form_submissions';

    protected static function newFactory(): Factory
    {
        return FormSubmissionFactory::new();
    }

    protected $fillable = [
        'form_id',
        'user_id',
        'assigned_to',
        'status',
        'ip_address',
        'user_agent',
        'referrer_url',
        'source_page_id',
        'country',
        'city',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'time_to_complete',
        'is_read',
        'is_spam',
        'is_starred',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_spam' => 'boolean',
            'is_starred' => 'boolean',
            'time_to_complete' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function values(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class, 'submission_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(FormSubmissionNote::class, 'submission_id');
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeSpam($query)
    {
        return $query->where('is_spam', true);
    }

    public function scopeNotSpam($query)
    {
        return $query->where('is_spam', false);
    }

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_to');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function getValueFor(string $fieldKey): ?string
    {
        $value = $this->values()->where('field_key', $fieldKey)->first();

        return $value?->value;
    }

    public function getEmailValue(): ?string
    {
        $value = $this->values()->where('field_type', 'email')->first();

        return $value?->value;
    }

    public function getRadicado(): string
    {
        return 'FORM-'.$this->created_at->format('Y').'-'.str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getCitizenName(): ?string
    {
        foreach ($this->values as $value) {
            $key = strtolower($value->field_key ?? '');

            if (str_contains($key, 'nombre') || str_contains($key, 'name') ||
                str_contains($key, 'first') || str_contains($key, 'primer')) {
                return $value->value;
            }
        }

        return null;
    }

    public function emails(): HasMany
    {
        return $this->hasMany(FormSubmissionEmail::class, 'submission_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(FormSubmissionAction::class, 'submission_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FormSubmissionFile::class, 'submission_id');
    }
}
