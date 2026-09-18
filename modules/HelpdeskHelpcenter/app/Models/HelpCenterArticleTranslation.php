<?php

namespace Modules\HelpdeskHelpcenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpCenterArticleTranslation extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_helpcenter_article_translations';

    protected $fillable = [
        'article_id',
        'locale',
        'title',
        'slug',
        'body',
        'meta_description',
        'meta_keywords',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpCenterArticle::class, 'article_id');
    }
}
