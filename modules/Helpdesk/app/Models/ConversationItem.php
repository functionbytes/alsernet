<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Helpdesk\Models\Concerns\HasCrossDatabaseUserRelation;

class ConversationItem extends Model
{
    use HasCrossDatabaseUserRelation, HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_items';

    protected $fillable = [
        'conversation_id',
        'author_id',
        'user_id',
        'type',
        'body',
        'html_body',
        'attachment_urls',
        'is_internal',
        'metadata',
        'external_id',
        'scheduled_at',
        'scheduled_by',
    ];

    protected function casts(): array
    {
        return [
            'attachment_urls' => 'array',
            'metadata' => 'array',
            'is_internal' => 'boolean',
            'scheduled_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Scope: Get only items pending scheduled send
     */
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at');
    }

    /**
     * Check if this item is pending scheduled send
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null;
    }

    /**
     * Get the conversation this item belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Get the item this message is quoting (reply-to)
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(ConversationItem::class, 'metadata->reply_to_id');
    }

    /**
     * Get the customer who authored this message (if from customer)
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'author_id');
    }

    /**
     * Get the staff user who authored this message (if from agent)
     * Note: User model is on default mysql connection, not helpdesk
     */
    public function user(): BelongsTo
    {
        return $this->belongsToUser('user_id', 'user');
    }

    /**
     * Get users who have read this message
     */
    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class, 'conversation_item_id');
    }

    /**
     * Scope: Get only messages (not system events)
     */
    public function scopeMessages($query)
    {
        return $query->where('type', 'message');
    }

    /**
     * Scope: Get only system events
     */
    public function scopeEvents($query)
    {
        return $query->where('type', '!=', 'message');
    }

    /**
     * Scope: Get only internal notes
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope: Get only external messages
     */
    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope: Get messages from customers
     */
    public function scopeFromCustomer($query)
    {
        return $query->whereNotNull('author_id')->whereNull('user_id');
    }

    /**
     * Scope: Get messages from agents
     */
    public function scopeFromAgent($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Check if this is a message (not a system event)
     */
    public function isMessage()
    {
        return $this->type === 'message';
    }

    /**
     * Check if this is a system event
     */
    public function isEvent()
    {
        return $this->type !== 'message';
    }

    /**
     * Check if message is from a customer
     */
    public function isFromCustomer()
    {
        return $this->author_id !== null && $this->user_id === null;
    }

    /**
     * Check if message is from an agent
     */
    public function isFromAgent()
    {
        return $this->user_id !== null;
    }

    /**
     * Get readable event type label
     */
    public function getEventLabelAttribute()
    {
        $labels = [
            'message' => 'Mensaje',
            'status_change' => 'Cambio de Estado',
            'assigned' => 'Asignado',
            'unassigned' => 'Desasignado',
            'closed' => 'Cerrado',
            'reopened' => 'Reabierto',
            'archived' => 'Archivado',
            'unarchived' => 'Desarchivado',
            'priority_changed' => 'Prioridad Cambiada',
            'internal_note' => 'Nota Interna',
            'attachment_added' => 'Adjunto Añadido',
            'customer_replied' => 'Respuesta del Cliente',
        ];

        return $labels[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    /**
     * Get badge color for event type
     */
    public function getEventColorAttribute()
    {
        $colors = [
            'message' => 'primary',
            'status_change' => 'info',
            'assigned' => 'success',
            'unassigned' => 'warning',
            'closed' => 'danger',
            'reopened' => 'success',
            'archived' => 'secondary',
            'unarchived' => 'info',
            'priority_changed' => 'warning',
            'internal_note' => 'secondary',
            'attachment_added' => 'info',
            'customer_replied' => 'primary',
        ];

        return $colors[$this->type] ?? 'secondary';
    }

    /**
     * Get the sender's name (customer or agent)
     */
    public function getSenderNameAttribute()
    {
        if ($this->isFromCustomer()) {
            return $this->author?->name ?? 'Desconocido';
        }

        if ($this->isFromAgent()) {
            return $this->user?->name ?? 'Agente';
        }

        return 'Sistema';
    }

    /**
     * Get the sender's avatar URL
     */
    public function getSenderAvatarAttribute()
    {
        if ($this->isFromCustomer()) {
            return $this->author?->getAvatarUrl();
        }

        if ($this->isFromAgent()) {
            return $this->user?->getAvatarUrl();
        }

        return null;
    }

    /**
     * Mark message as read by a user
     */
    public function markAsRead($userId)
    {
        return $this->reads()->firstOrCreate([
            'user_id' => $userId,
        ]);
    }

    /**
     * Get read count for this message
     */
    public function getReadCount()
    {
        return $this->reads()->distinct('user_id')->count();
    }

    /**
     * Check if read by a specific user
     */
    public function isReadByUser($userId)
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Get list of users who have read this message
     */
    public function getReadByUsers()
    {
        $userIds = $this->reads()
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Get body content (prefer HTML over plain text)
     */
    public function getContentAttribute()
    {
        return $this->html_body ?? $this->body;
    }

    /**
     * Get plain text version of body
     */
    public function getPlainTextAttribute()
    {
        if ($this->html_body) {
            return strip_tags($this->html_body);
        }

        return $this->body;
    }

    /**
     * Normaliza URLs de attachments: si una URL absoluta apunta a un host
     * antiguo (ej. cuando se migró el sitio entre dominios), se reescribe
     * con el host actual del APP_URL para que el archivo siga sirviéndose
     * desde el symlink /storage local.
     */
    public function getAttachmentUrlsAttribute($value): array
    {
        if (is_string($value)) {
            $urls = json_decode($value, true);
        } else {
            $urls = $value;
        }
        if (! is_array($urls)) {
            return [];
        }

        $appUrl = rtrim(config('app.url') ?: url('/'), '/');

        return array_map(function ($url) use ($appUrl) {
            if (! is_string($url) || $url === '') {
                return $url;
            }
            // Si es path relativo /storage/... lo dejamos.
            if (str_starts_with($url, '/')) {
                return $url;
            }
            // Si es absoluta, comprueba host. Si difiere del actual, reemplaza por APP_URL.
            if (preg_match('#^https?://[^/]+(/.*)$#', $url, $m)) {
                $path = $m[1];
                // Solo reescribimos si el path empieza con /storage/ (nuestro disk public)
                if (str_starts_with($path, '/storage/')) {
                    return $appUrl.$path;
                }
            }

            return $url;
        }, $urls);
    }

    /**
     * Get safe HTML version of body with @mentions converted to chips.
     * Fallback al body plano con nl2br + e() cuando no hay menciones.
     */
    public function getBodyHtmlAttribute(): string
    {
        $body = nl2br(e($this->body ?? ''));

        // Auto-linkify URLs (http/https) so the agent can click them. Matches
        // `https?://` followed by anything that is not whitespace or HTML
        // delimiter, then trims trailing punctuation.
        $body = preg_replace_callback(
            '#(https?://[^\s<>"\']+)#i',
            function ($m) {
                $url = $m[1];
                $trim = '';
                while (in_array(substr($url, -1), ['.', ',', ';', ':', '!', '?', ')', ']'], true)) {
                    $trim = substr($url, -1).$trim;
                    $url = substr($url, 0, -1);
                }

                return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer" class="bv-msg-link">'.e($url).'</a>'.$trim;
            },
            $body
        );

        return preg_replace_callback(
            '/(^|\s|>)@([\p{L}0-9._-]+)/u',
            fn ($m) => $m[1].'<span class="bv-mention-chip" data-bv-mention-handle="'.e($m[2]).'">@'.e($m[2]).'</span>',
            $body
        );
    }

    /**
     * Check if message has attachments
     */
    public function hasAttachments()
    {
        return ! empty($this->attachment_urls) && count($this->attachment_urls) > 0;
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute()
    {
        return $this->hasAttachments() ? count($this->attachment_urls) : 0;
    }
}
