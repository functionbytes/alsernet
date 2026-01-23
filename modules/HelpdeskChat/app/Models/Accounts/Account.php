<?php

namespace Modules\HelpdeskChat\Models\Accounts;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HelpdeskChat\Models\Automations\Automation;
use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Contacts\ContactAttribute;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Conversations\ConversationMessage;
use Modules\HelpdeskChat\Models\Integrations\Integration;
use Modules\HelpdeskChat\Models\Label;
use Modules\HelpdeskChat\Models\Macro;
use Modules\HelpdeskChat\Models\Sla\SlaPolicy;
use Modules\HelpdeskChat\Models\Teams\Team;
use Modules\HelpdeskChat\Models\Teams\TeamRole;
use Modules\HelpdeskChat\Models\Webhook;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'helpdesk_accounts';

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
        return $this->hasMany(User::class);
    }

    /**
     * Get all contacts for this account.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
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
        return $this->hasMany(Label::class, 'account_id');
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
        return $this->hasMany(ContactAttribute::class, 'account_id');
    }
}
