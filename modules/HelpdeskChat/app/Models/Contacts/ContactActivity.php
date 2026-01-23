<?php

namespace Modules\HelpdeskChat\Models\Contacts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\HelpdeskChat\Models\Accounts\Account;

class ContactActivity extends Model
{
    protected $fillable = [
        'account_id',
        'contact_id',
        'user_id',
        'activity_type',
        'description',
        'metadata',
        'subject_type',
        'subject_id',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Get the account this activity belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact this activity belongs to.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the user who performed this activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (polymorphic).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log a contact activity.
     */
    public static function log(
        int $contactId,
        string $activityType,
        string $description,
        ?int $userId = null,
        ?Model $subject = null,
        array $metadata = []
    ): self {
        $contact = Contact::find($contactId);

        return self::create([
            'account_id' => $contact->account_id,
            'contact_id' => $contactId,
            'user_id' => $userId ?? auth()->id(),
            'activity_type' => $activityType,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get activities for a contact.
     */
    public static function forContact(int $contactId)
    {
        return self::where('contact_id', $contactId)
            ->with(['user', 'subject'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get activity icon based on type.
     */
    public function getIcon(): string
    {
        return match ($this->activity_type) {
            'conversation_created' => 'bi-chat-dots',
            'message_sent' => 'bi-send',
            'message_received' => 'bi-envelope',
            'contact_updated' => 'bi-pencil',
            'label_added' => 'bi-tag',
            'label_removed' => 'bi-tag-fill',
            'note_added' => 'bi-sticky',
            'note_updated' => 'bi-sticky-fill',
            'note_deleted' => 'bi-trash',
            'contact_created' => 'bi-person-plus',
            'contact_merged' => 'bi-shuffle',
            'custom_attribute_updated' => 'bi-sliders',
            default => 'bi-circle',
        };
    }

    /**
     * Get activity color class.
     */
    public function getColorClass(): string
    {
        return match ($this->activity_type) {
            'conversation_created', 'contact_created' => 'text-success',
            'message_sent', 'message_received' => 'text-primary',
            'contact_updated', 'note_updated', 'custom_attribute_updated' => 'text-warning',
            'label_removed', 'note_deleted', 'contact_merged' => 'text-danger',
            default => 'text-secondary',
        };
    }
}
