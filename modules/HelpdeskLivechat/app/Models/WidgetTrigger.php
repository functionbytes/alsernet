<?php

namespace Modules\HelpdeskLivechat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskLivechat\Models\Channels\Web;

/**
 * Disparador proactivo del widget: si el visitante cumple las condiciones,
 * se abre el chat (con o sin mensaje proactivo). Se evalúa en el navegador
 * (widget-triggers.ts); aquí solo se guarda y se sirve.
 */
class WidgetTrigger extends Model
{
    public const CONDITION_TYPES = [
        'site_time', 'page_time', 'pages_visited', 'products_viewed', 'product_viewed',
        'current_product', 'url', 'locale', 'weekday', 'hour_range', 'cart_value', 'cart_items',
    ];

    public const ACTIONS = ['open_chat', 'message'];

    public const FREQUENCIES = ['once_visitor', 'once_session', 'every_page'];

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_widget_triggers';

    protected $fillable = [
        'web_id', 'name', 'is_active', 'priority', 'match', 'conditions', 'action', 'message', 'frequency',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'conditions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $forget = fn (self $t) => Cache::forget(self::cacheKey((int) $t->web_id));
        static::saved($forget);
        static::deleted($forget);
    }

    public static function cacheKey(int $webId): string
    {
        return 'helpdesklivechat:widget_triggers:'.$webId;
    }

    public function web(): BelongsTo
    {
        return $this->belongsTo(Web::class, 'web_id');
    }

    /**
     * Lo que recibe el widget (sin datos internos).
     *
     * @return array<string, mixed>
     */
    public function toWidgetArray(): array
    {
        return [
            'id' => $this->id,
            'match' => $this->match,
            'conditions' => array_values((array) $this->conditions),
            'action' => $this->action,
            'message' => $this->action === 'message' ? $this->message : null,
            'frequency' => $this->frequency,
        ];
    }
}
