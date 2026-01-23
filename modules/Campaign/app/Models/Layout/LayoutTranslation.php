<?php

namespace Modules\Campaign\Models\Layout;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uid
 * @property int $layout_id
 * @property int $lang_id
 * @property string|null $subject
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Modules\Campaign\Models\Layout\Layout $layout
 * @property-read \App\Models\Lang $lang
 */
class LayoutTranslation extends Model
{
    use HasUid;

    protected $table = 'layout_langs';

    protected $fillable = [
        'uid',
        'layout_id',
        'lang_id',
        'subject',
        'content',
    ];

    /**
     * Relación con Layout
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class, 'layout_id', 'id');
    }

    /**
     * Relación con Lang
     */
    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang', 'lang_id', 'id');
    }
}
