<?php

namespace Modules\Chat\Models\Accounts;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Chat\Database\Factories\AccountFactory;
use Modules\Chat\Models\Automations\Automation;
use Modules\Chat\Models\Conversations\Conversation;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Customers\Customer;
use Modules\Chat\Models\Customers\CustomerActivity;
use Modules\Chat\Models\Customers\CustomerAttribute;
use Modules\Chat\Models\Customers\CustomerNote;
use Modules\Chat\Models\Customers\CustomerView;
use Modules\Chat\Models\Inbox\Inbox;
use Modules\Chat\Models\Integrations\Integration;
use Modules\Chat\Models\Macro;
use Modules\Chat\Models\Sla\SlaPolicy;
use Modules\Chat\Models\Teams\Team;
use Modules\Chat\Models\Teams\TeamRole;
use Modules\Chat\Models\Webhook;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chat_accounts';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return AccountFactory::new();
    }

    protected $fillable = [
        'name',
        'default_locale',
        'supported_locales',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'supported_locales' => 'array',
        ];
    }

    /**
     * Get supported locales for this account.
     */
    public function getSupportedLocales(): array
    {
        if ($this->supported_locales) {
            return $this->supported_locales;
        }

        return array_keys(config('locales.supported', []));
    }

    /**
     * Check if locale is supported by this account.
     */
    public function supportsLocale(string $locale): bool
    {
        return in_array($locale, $this->getSupportedLocales());
    }

    /**
     * Get all users for this account.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'enterprise_id', 'id');
    }

    /**
     * Get all customers for this account.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'account_id');
    }

    /**
     * Get all inboxes for this account.
     */
    public function inboxes(): HasMany
    {
        return $this->hasMany(Inbox::class);
    }

    /**
     * Get all conversation for this account.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get all messages for this account.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'account_id');
    }

    /**
     * Get all teams for this account.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class, 'account_id');
    }

    /**
     * Get all labels for this account.
     */
    public function labels(): HasMany
    {
        return $this->hasMany(ConversationLabel::class, 'account_id');
    }

    /**
     * Get all macros for this account.
     */
    public function macros(): HasMany
    {
        return $this->hasMany(Macro::class, 'account_id');
    }

    /**
     * Get all webhooks for this account.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class, 'account_id');
    }

    /**
     * Get all integrations hooks for this account.
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class, 'account_id');
    }

    /**
     * Get all SLA policies for this account.
     */
    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class, 'account_id');
    }

    /**
     * Get all team roles for this account.
     */
    public function teamRoles(): HasMany
    {
        return $this->hasMany(TeamRole::class, 'account_id');
    }

    /**
     * Get all automations for this account.
     */
    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class, 'account_id');
    }

    /**
     * Get all custom attribute definitions for this account.
     */
    public function customAttributeDefinitions(): HasMany
    {
        return $this->hasMany(CustomerAttribute::class, 'account_id');
    }

    /**
     * Get customer notes for this account.
     */
    public function customerNotes(): HasMany
    {
        return $this->hasMany(CustomerNote::class, 'account_id');
    }

    /**
     * Get customer activities for this account.
     */
    public function customerActivities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class, 'account_id');
    }

    /**
     * Get customer views for this account.
     */
    public function customerViews(): HasMany
    {
        return $this->hasMany(CustomerView::class, 'account_id');
    }
}
