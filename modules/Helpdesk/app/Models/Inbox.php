<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Channels\Api;
use Modules\Helpdesk\Models\Channels\Email;
use Modules\Helpdesk\Models\Channels\Facebook;
use Modules\Helpdesk\Models\Channels\Instagram;
use Modules\Helpdesk\Models\Channels\Sms;
use Modules\Helpdesk\Models\Channels\Whatsapp;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Nwidart\Modules\Facades\Module;

class Inbox extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_inboxes';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_FACEBOOK = 'facebook';

    public const CHANNEL_INSTAGRAM = 'instagram';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_WEB = 'web';

    public const CHANNEL_TYPES = [
        self::CHANNEL_WHATSAPP,
        self::CHANNEL_FACEBOOK,
        self::CHANNEL_INSTAGRAM,
        self::CHANNEL_EMAIL,
        self::CHANNEL_WEB,
    ];

    /**
     * Channel types currently available for new inboxes.
     *
     * The "web" channel (live chat widget) is only offered when the
     * HelpdeskLivechat module is installed and enabled. Existing inboxes
     * with channel_type='web' keep working even if the module is later
     * disabled, but no new ones can be created from the UI.
     *
     * @return list<string>
     */
    public static function availableChannelTypes(): array
    {
        $available = self::CHANNEL_TYPES;

        if (! Module::find('HelpdeskLivechat')?->isEnabled()) {
            $available = array_values(array_filter(
                $available,
                fn ($type) => $type !== self::CHANNEL_WEB
            ));
        }

        return $available;
    }

    protected $fillable = [
        'uid',
        'brand_id',
        'name',
        'channel_type',
        'channel_id',
        'account_id',
        'timezone',
        'is_active',
        'credentials',
        'default_assignee_id',
        'default_group_id',
        'greeting_enabled',
        'greeting_message',
        'working_hours_enabled',
        'working_hours',
        'out_of_office_message',
        'color',
        'icon',
        'description',
    ];

    /**
     * Map of channel_type slug → polymorphic model class. Used by the
     * morphTo() relation and `enforceMorphMap` in the service provider.
     */
    public const CHANNEL_TYPE_MAP = [
        'web' => Web::class,
        'email' => Email::class,
        'facebook' => Facebook::class,
        'instagram' => Instagram::class,
        'sms' => Sms::class,
        'whatsapp' => Whatsapp::class,
        'api' => Api::class,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'greeting_enabled' => 'boolean',
            'working_hours_enabled' => 'boolean',
            'working_hours' => 'array',
        ];
    }

    /**
     * Auto-genera UID al crear si no se proporcionó.
     */
    protected static function booted(): void
    {
        static::creating(function (self $inbox) {
            if (empty($inbox->uid)) {
                $inbox->uid = (string) Str::uuid();
            }
        });
    }

    // ─── Relaciones ─────────────────────────────────────────────────

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function defaultGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'default_group_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'inbox_id');
    }

    /**
     * Polymorphic relation to the underlying channel implementation
     * (Web, Email, Facebook, Instagram, Sms, Whatsapp, Api).
     */
    public function channel(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Customers that have interacted via this inbox (pivot helpdesk_customer_inboxes).
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'helpdesk_customer_inboxes')
            ->withPivot(['source_id', 'last_seen_at'])
            ->withTimestamps();
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByChannel($query, string $channelType)
    {
        return $query->where('channel_type', $channelType);
    }

    // ─── Credenciales encriptadas ───────────────────────────────────
    // Las credenciales se guardan encriptadas con APP_KEY usando Laravel Crypt.

    public function getCredentialsAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }
        try {
            $decrypted = Crypt::decryptString($value);
            $arr = json_decode($decrypted, true);

            return is_array($arr) ? $arr : [];
        } catch (\Throwable $e) {
            // Si no se pudo desencriptar (datos antiguos o corruptos), intentar como JSON plano
            $arr = json_decode($value, true);

            return is_array($arr) ? $arr : [];
        }
    }

    public function setCredentialsAttribute($value): void
    {
        $arr = is_array($value) ? $value : (json_decode((string) $value, true) ?: []);
        $this->attributes['credentials'] = empty($arr) ? null : Crypt::encryptString(json_encode($arr));
    }

    /**
     * Helper: obtiene una credencial específica.
     */
    public function getCredential(string $key, $default = null)
    {
        return data_get($this->credentials, $key, $default);
    }

    // ─── Webhook URL público para este inbox ────────────────────────

    public function webhookUrl(): string
    {
        return url('webhooks/helpdesk/inbox/'.$this->uid);
    }

    // ─── Labels para la UI ──────────────────────────────────────────

    public static function channelLabels(): array
    {
        return [
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            self::CHANNEL_FACEBOOK => 'Facebook Messenger',
            self::CHANNEL_INSTAGRAM => 'Instagram DMs',
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_WEB => 'Website (chat widget)',
        ];
    }

    /**
     * Channel labels filtered by which channel types are currently available
     * (respects optional modules like HelpdeskLivechat being installed).
     *
     * @return array<string, string>
     */
    public static function availableChannelLabels(): array
    {
        return array_intersect_key(
            self::channelLabels(),
            array_flip(self::availableChannelTypes())
        );
    }

    public function channelLabel(): string
    {
        return self::channelLabels()[$this->channel_type] ?? ucfirst($this->channel_type);
    }

    public static function channelIcons(): array
    {
        return [
            self::CHANNEL_WHATSAPP => 'fab fa-whatsapp',
            self::CHANNEL_FACEBOOK => 'fab fa-facebook-messenger',
            self::CHANNEL_INSTAGRAM => 'fab fa-instagram',
            self::CHANNEL_EMAIL => 'far fa-envelope',
            self::CHANNEL_WEB => 'far fa-comment-dots',
        ];
    }

    public function channelIcon(): string
    {
        return self::channelIcons()[$this->channel_type] ?? 'fas fa-inbox';
    }

    /**
     * Lista de campos esperados por canal — usada por el form de edit.
     *
     * @return array<int,array{key:string,label:string,type:string,required?:bool,help?:string}>
     */
    public static function credentialFields(string $channelType): array
    {
        return match ($channelType) {
            self::CHANNEL_WHATSAPP => [
                ['key' => 'business_account_id', 'label' => 'Business Account ID', 'type' => 'text', 'required' => true],
                ['key' => 'phone_number_id', 'label' => 'Phone Number ID', 'type' => 'text', 'required' => true],
                ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => true, 'help' => 'Token permanente de Meta Cloud API'],
                ['key' => 'verify_token', 'label' => 'Verify Token', 'type' => 'text', 'required' => true, 'help' => 'Cualquier string aleatorio que pegarás también en Meta'],
                ['key' => 'app_secret', 'label' => 'App Secret', 'type' => 'password', 'help' => 'Para validar firma de webhooks'],
            ],
            self::CHANNEL_FACEBOOK => [
                ['key' => 'page_id', 'label' => 'Page ID', 'type' => 'text', 'required' => true],
                ['key' => 'page_access_token', 'label' => 'Page Access Token', 'type' => 'password', 'required' => true],
                ['key' => 'app_id', 'label' => 'App ID', 'type' => 'text'],
                ['key' => 'app_secret', 'label' => 'App Secret', 'type' => 'password'],
                ['key' => 'verify_token', 'label' => 'Verify Token', 'type' => 'text', 'required' => true],
            ],
            self::CHANNEL_INSTAGRAM => [
                ['key' => 'business_account_id', 'label' => 'Business Account ID', 'type' => 'text', 'required' => true],
                ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'password', 'required' => true],
                ['key' => 'verify_token', 'label' => 'Verify Token', 'type' => 'text', 'required' => true],
            ],
            self::CHANNEL_EMAIL => [
                ['key' => 'inbound_address', 'label' => 'Email entrante', 'type' => 'text', 'required' => true, 'help' => 'Dirección a la que tus clientes escriben'],
                ['key' => 'provider', 'label' => 'Proveedor del webhook', 'type' => 'text', 'help' => 'mailgun, sendgrid, postmark o generic'],
                ['key' => 'forward_address', 'label' => 'Dirección de reenvío', 'type' => 'text', 'help' => 'Opcional: reenviar a este buzón externo'],
            ],
            self::CHANNEL_WEB => [
                ['key' => 'site_url', 'label' => 'URL del sitio', 'type' => 'text', 'required' => true],
                ['key' => 'allowed_origins', 'label' => 'Orígenes permitidos (CORS)', 'type' => 'text', 'help' => 'Separados por coma'],
                ['key' => 'primary_color', 'label' => 'Color primario', 'type' => 'text'],
            ],
            default => [],
        };
    }
}
