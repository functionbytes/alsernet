<?php

namespace Modules\HelpdeskLivechat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetPageView extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_widget_page_views';

    protected $fillable = [
        'session_id',
        'url',
        'title',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(WidgetSession::class, 'session_id');
    }
}
