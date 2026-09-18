<?php

namespace Modules\HelpdeskHelpcenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpCenterArticleVote extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_helpcenter_article_votes';

    protected $fillable = [
        'article_id',
        'cookie_id',
        'ip_hash',
        'vote',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'vote' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpCenterArticle::class, 'article_id');
    }
}
