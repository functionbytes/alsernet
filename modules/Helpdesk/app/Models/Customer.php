<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Database\Factories\CustomerFactory;
use Modules\Helpdesk\Models\Concerns\HasCustomAttributes;
use Modules\Helpdesk\Services\PhoneNormalizerService;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use HasCustomAttributes, HasFactory, LogsActivity, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_id',
        'erp_customer_id',
        'erp_synced_at',
        'is_blocked',
        'avatar_url',
        'country',
        'state',
        'city',
        'postal_code',
        'language',
        'timezone',
        'custom_attributes',
        'email_verified_at',
        'banned_at',
        'ban_reason',
        'last_seen_at',
        'total_conversations',
        'total_page_visits',
        'internal_notes',
        'whatsapp_phone',
        'facebook_psid',
        'instagram_id',
        'portal_token',
        'portal_token_expires_at',
        'portal_password',
    ];

    protected $hidden = [
        'portal_password',
        'portal_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'custom_attributes' => 'array',
            'portal_token_expires_at' => 'datetime',
            'erp_synced_at' => 'datetime',
            'is_blocked' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Previously had is_banned and is_verified in $appends, which caused
    // unnecessary computation on every serialization. Removed for performance;
    // use ->append('is_banned') or ->append('is_verified') explicitly when needed.

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'internal_notes', 'banned_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('contact');
    }

    /**
     * Get all tickets for this customer
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    /**
     * External IDs across integrated platforms (PrestaShop, ERP, Shopify, ...).
     * Allows a single customer to be linked to multiple platform IDs.
     */
    public function externalIds(): HasMany
    {
        return $this->hasMany(CustomerExternalId::class, 'customer_id');
    }

    /**
     * Resolve a customer by their external ID on a specific platform.
     */
    public static function findByExternalId(string $platform, string $externalId): ?self
    {
        $link = CustomerExternalId::query()
            ->forExternal($platform, $externalId)
            ->first();

        return $link?->customer;
    }

    /**
     * Link this customer with an external platform ID. Idempotent.
     */
    public function linkExternalId(string $platform, string $externalId, ?array $metadata = null): CustomerExternalId
    {
        return $this->externalIds()->updateOrCreate(
            ['platform' => $platform, 'external_id' => $externalId],
            ['metadata' => $metadata],
        );
    }

    /**
     * Returns the external_id on a given platform, or null.
     */
    public function externalIdFor(string $platform): ?string
    {
        return $this->externalIds->firstWhere('platform', $platform)?->external_id;
    }

    /**
     * Get all conversations for this customer
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'customer_id');
    }

    /**
     * Get all sessions for this customer
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CustomerSession::class, 'customer_id');
    }

    /**
     * Get the latest session
     */
    public function latestSession(): HasOne
    {
        return $this->hasOne(CustomerSession::class, 'customer_id')
            ->latest('created_at');
    }

    /**
     * Inboxes the customer has interacted with (pivot helpdesk_customer_inboxes).
     */
    public function inboxes(): BelongsToMany
    {
        return $this->belongsToMany(Inbox::class, 'helpdesk_customer_inboxes')
            ->withPivot(['source_id', 'last_seen_at'])
            ->withTimestamps();
    }

    /**
     * Get all page visits
     */
    public function pageVisits(): HasMany
    {
        return $this->hasMany(PageVisit::class, 'customer_id');
    }

    /**
     * Scope: Get only banned customers
     */
    public function scopeBanned($query)
    {
        return $query->whereNotNull('banned_at');
    }

    /**
     * Scope: Get only verified customers
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope: Get only active (not banned) customers
     */
    public function scopeActive($query)
    {
        return $query->whereNull('banned_at');
    }

    /**
     * Scope: Search by name or email
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('whatsapp_phone', 'like', "%{$term}%");
        });
    }

    /**
     * Scope: restrict to customers the given agent may access.
     *
     * Reuses the inbox-isolation mechanism of CustomerPolicy::sharesInboxWith():
     * managers (helpdesk.manage / helpdesk.customers.manage) see everyone; agents
     * only see customers with at least one conversation in an assigned inbox, or
     * associated to one of those inboxes via the helpdesk_customer_inboxes pivot
     * (contactos creados/importados sin conversación todavía).
     * A restricted agent with no assigned inboxes sees nothing (fail-closed).
     */
    public function scopeForAgent(Builder $query, User $user): Builder
    {
        // can(), no hasPermissionTo(): este scope se invoca directo desde controllers,
        // sin pasar por Gate::authorize(), asi que hasPermissionTo() se salta el
        // Gate::before que le da acceso total al rol super-settings (AuthServiceProvider) —
        // un super admin quedaba tratado como agente restringido sin bandejas asignadas.
        if ($user->can('helpdesk.manage') || $user->can('helpdesk.customers.manage')) {
            return $query;
        }

        $inboxIds = AgentInboxCapacity::query()
            ->where('user_id', $user->id)
            ->pluck('inbox_id');

        return $query->where(fn (Builder $q) => $q
            ->whereHas('conversations', fn (Builder $c) => $c->whereIn('inbox_id', $inboxIds))
            ->orWhereHas('inboxes', fn (Builder $i) => $i->whereIn('helpdesk_customer_inboxes.inbox_id', $inboxIds))
        );
    }

    /**
     * Check if customer is banned
     */
    public function getIsBannedAttribute()
    {
        return $this->banned_at !== null;
    }

    /**
     * Check if customer email is verified
     */
    public function getIsVerifiedAttribute()
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Ban a customer
     */
    public function ban($reason = null)
    {
        $this->update([
            'banned_at' => now(),
            'ban_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Unban a customer
     */
    public function unban()
    {
        $this->update([
            'banned_at' => null,
            'ban_reason' => null,
        ]);

        return $this;
    }

    /**
     * Verify customer email
     */
    public function verifyEmail()
    {
        $this->update([
            'email_verified_at' => now(),
        ]);

        return $this;
    }

    /**
     * Update last seen activity
     */
    public function updateLastSeen()
    {
        $this->update([
            'last_seen_at' => now(),
        ]);

        return $this;
    }

    /**
     * Increment conversation count
     */
    public function incrementConversationCount()
    {
        $this->increment('total_conversations');
    }

    /**
     * Increment page visit count
     */
    public function incrementPageVisitCount()
    {
        $this->increment('total_page_visits');
    }

    /**
     * Get unread conversations count
     */
    public function getUnreadConversationsCount()
    {
        return $this->conversations()
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->count();
    }

    /**
     * Get avatar URL or default
     */
    public function getAvatarUrl(): string
    {
        return $this->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($this->name);
    }

    /**
     * Normaliza el teléfono al guardar: elimina espacios, guiones y convierte 0034→+34.
     * Garantiza formato consistente independientemente del canal de entrada.
     */
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = app(PhoneNormalizerService::class)->normalize($value);
    }

    /**
     * Normaliza el teléfono de WhatsApp al guardar (mismo tratamiento que phone).
     */
    public function setWhatsappPhoneAttribute(?string $value): void
    {
        $this->attributes['whatsapp_phone'] = app(PhoneNormalizerService::class)->normalize($value);
    }

    /**
     * Generate a portal authentication token.
     *
     * Stores the SHA-256 hash of the token in portal_token (never the cleartext),
     * and returns the cleartext token for use in the email link.
     * The token expires in 30 minutes.
     *
     * Note for CustomerPortalController::authenticate(): use findByPortalToken($token)
     * to validate — it performs the hash comparison internally.
     */
    public function generatePortalToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->update([
            'portal_token' => hash('sha256', $token),
            'portal_token_expires_at' => now()->addMinutes(30),
        ]);

        return $token;
    }

    /**
     * Find a customer by their cleartext portal token.
     *
     * Hashes the provided token with SHA-256 before querying,
     * and verifies the token has not expired.
     */
    public static function findByPortalToken(string $token): ?self
    {
        return static::query()
            ->where('portal_token', hash('sha256', $token))
            ->where('portal_token_expires_at', '>', now())
            ->first();
    }

    protected static function newFactory(): CustomerFactory
    {
        return new CustomerFactory;
    }
}
