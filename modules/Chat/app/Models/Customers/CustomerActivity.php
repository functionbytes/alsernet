<?php

namespace Modules\Chat\Models\Customers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Chat\Models\Accounts\Account;

class CustomerActivity extends Model
{
    protected $table = 'chat_customer_activities';

    protected $fillable = [
        'account_id',
        'customer_id',
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
     * Get the customer this activity belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
     * Log a customer activity.
     */
    public static function log(
        int $customerId,
        string $activityType,
        string $description,
        ?int $userId = null,
        ?Model $subject = null,
        array $metadata = []
    ): self {
        $customer = Customer::find($customerId);

        return self::create([
            'account_id' => $customer->account_id,
            'customer_id' => $customerId,
            'user_id' => $userId ?? auth()->id(),
            'activity_type' => $activityType,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get activities for a customer.
     */
    public static function forCustomer(int $customerId)
    {
        return self::where('customer_id', $customerId)
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
