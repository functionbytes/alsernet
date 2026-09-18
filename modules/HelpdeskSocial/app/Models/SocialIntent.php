<?php

namespace Modules\HelpdeskSocial\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\HelpdeskSocial\Database\Factories\SocialIntentFactory;

class SocialIntent extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_intents';

    public $timestamps = true;

    protected $fillable = [
        'classifiable_type',
        'classifiable_id',
        'platform',
        'intent',
        'confidence',
        'classifier',
        'urgency',
        'keywords_matched',
        'entities',
        'raw_response',
        'classified_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'keywords_matched' => 'array',
            'entities' => 'array',
            'raw_response' => 'array',
            'classified_at' => 'datetime',
        ];
    }

    public function classifiable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): SocialIntentFactory
    {
        return SocialIntentFactory::new();
    }

    public const INTENTS = [
        'query' => 'Consulta',
        'complaint' => 'Queja',
        'purchase_interest' => 'Interés de compra',
        'spam' => 'Spam',
        'positive' => 'Comentario positivo',
        'neutral' => 'Neutral',
        'other' => 'Otro',
    ];

    public const URGENCY_LEVELS = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'critical' => 'Crítica',
    ];
}
