<?php

namespace Modules\Campaign\Models\Layout;

use App\Models\Lang;
use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uid
 * @property int $layout_id
 * @property int $lang_id
 * @property string|null $subject
 * @property string $content
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Layout $layout
 * @property-read Lang $lang
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
