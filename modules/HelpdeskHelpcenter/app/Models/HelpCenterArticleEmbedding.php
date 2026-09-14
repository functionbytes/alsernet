<?php

namespace Modules\HelpdeskHelpcenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpCenterArticleEmbedding extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_helpcenter_article_embeddings';

    protected $fillable = [
        'article_id',
        'locale',
        'chunk_index',
        'chunk_text',
        'embedding',
        'model',
        'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'chunk_index' => 'integer',
            'dimensions' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpCenterArticle::class, 'article_id');
    }
}
