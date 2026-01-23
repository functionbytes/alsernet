<?php

namespace Modules\HelpdeskChat\Models\Contacts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;
use Modules\HelpdeskChat\Models\Accounts\Account;
use Modules\HelpdeskChat\Models\Accounts\Inbox;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Label;

class Contact extends Model
{
    use Searchable;

    protected $table = 'helpdesk_contacts';

    protected $fillable = [
        'account_id',
        'name',
        'last_name',
        'email',
        'phone_number',
        'company_name',
        'bio',
        'city',
        'country',
        'country_code',
        'social_profiles',
        'blocked',
        'last_activity_at',
        'additional_attributes',
    ];

    protected function casts(): array
    {
        return [
            'additional_attributes' => 'array',
            'social_profiles' => 'array',
            'blocked' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'company_name' => $this->company_name,
            'bio' => $this->bio,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }

    /**
     * Get the account that owns the contact.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all contact inboxes for this contact.
     */
    public function contactInboxes(): HasMany
    {
        return $this->hasMany(ContactInbox::class);
    }

    /**
     * Get all conversation for this contact.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get all inboxes this contact is connected to.
     */
    public function inboxes(): HasManyThrough
    {
        return $this->hasManyThrough(Inbox::class, ContactInbox::class);
    }

    /**
     * Get all labels for this contact.
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class)->withTimestamps();
    }

    /**
     * Get all notes for this contact.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class)->latest();
    }

    /**
     * Get all segments this contact belongs to.
     */
    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(ContactSegment::class, 'contact_segment_contact')
            ->withTimestamps()
            ->withPivot('added_at');
    }

    /**
     * Get full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->name.' '.$this->last_name);
    }

    /**
     * Check if contact is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    /**
     * Block this contact.
     */
    public function block(): void
    {
        $this->update(['blocked' => true]);
    }

    /**
     * Unblock this contact.
     */
    public function unblock(): void
    {
        $this->update(['blocked' => false]);
    }

    /**
     * Update last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Get the avatar for this contact.
     */
    public function avatar(): HasOne
    {
        return $this->hasOne(ContactAvatar::class);
    }

    /**
     * Get avatar URL (uploaded or Gravatar fallback).
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return $this->avatar->url;
        }

        return $this->getGravatarUrl();
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->avatar && $this->avatar->thumbnail_url) {
            return $this->avatar->thumbnail_url;
        }

        return $this->getGravatarUrl(50);
    }

    /**
     * Get Gravatar URL.
     */
    public function getGravatarUrl(int $size = 200): string
    {
        $hash = md5(strtolower(trim($this->email ?? '')));

        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }

    /**
     * Get initials for avatar placeholder.
     */
    public function getInitialsAttribute(): string
    {
        $firstInitial = $this->name ? strtoupper(substr($this->name, 0, 1)) : '';
        $lastInitial = $this->last_name ? strtoupper(substr($this->last_name, 0, 1)) : '';

        return $firstInitial.$lastInitial ?: '?';
    }
}
