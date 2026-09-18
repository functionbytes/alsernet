<?php

namespace Modules\Supplier\Models\Prompt;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptVersion extends Model
{
    protected $table = 'supplier_prompt_versions';

    protected $fillable = [
        'prompt_id',
        'version',
        'label',
        'scope',
        'content_type',
        'output_language',
        'tone',
        'priority',
        'seo_focus',
        'enable_web_search',
        'prompt_template',
        'saved_by',
    ];

    protected function casts(): array
    {
        return [
            'version'           => 'integer',
            'priority'          => 'integer',
            'seo_focus'         => 'boolean',
            'enable_web_search' => 'boolean',
        ];
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class, 'prompt_id');
    }

    public function savedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saved_by');
    }
}
