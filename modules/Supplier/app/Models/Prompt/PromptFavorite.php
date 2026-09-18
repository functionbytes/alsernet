<?php

namespace Modules\Supplier\Models\Prompt;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot-style record marking a prompt as a favourite for a user.
 *
 * The table only tracks creation time (no updated_at), enforces a unique
 * (user_id, prompt_id) pair and cascades on delete of either side.
 */
class PromptFavorite extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'supplier_prompt_favorites';

    protected $fillable = [
        'user_id',
        'prompt_id',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'prompt_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'prompt_id');
    }
}
