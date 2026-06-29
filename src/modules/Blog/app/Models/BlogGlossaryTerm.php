<?php

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogGlossaryTerm extends Model
{
    protected $table = 'blog_glossary_terms';

    protected $fillable = [
        'source_term',
        'source_locale',
        'target_term',
        'target_locale',
        'do_not_translate',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'do_not_translate' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
