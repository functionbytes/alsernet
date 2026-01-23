<?php

namespace Modules\HelpdeskChat\Models\Contacts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\HelpdeskChat\Models\Accounts\Account;

class ContactSegment extends Model
{
    protected $fillable = [
        'account_id',
        'user_id',
        'name',
        'description',
        'filter_criteria',
        'is_dynamic',
        'contact_count',
    ];

    protected function casts(): array
    {
        return [
            'filter_criteria' => 'array',
            'is_dynamic' => 'boolean',
            'contact_count' => 'integer',
        ];
    }

    /**
     * Segment belongs to an account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Segment belongs to a user (creator).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Contacts in this segment.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_segment_contact')
            ->withTimestamps()
            ->withPivot('added_at');
    }

    /**
     * Scope to filter segments by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope to filter dynamic segments.
     */
    public function scopeDynamic($query)
    {
        return $query->where('is_dynamic', true);
    }

    /**
     * Update the contact count cache.
     */
    public function updateContactCount(): void
    {
        $this->update(['contact_count' => $this->contacts()->count()]);
    }
}
