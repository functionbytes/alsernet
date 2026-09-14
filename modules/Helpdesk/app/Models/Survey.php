<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_surveys';

    protected $fillable = [
        'name',
        'trigger_type',
        'trigger_config',
        'questions',
        'is_active',
    ];

    const TRIGGER_TYPES = [
        'conversation_closed' => 'Conversacion cerrada',
        'manual' => 'Manual',
        'csat_low' => 'CSAT bajo',
        'tag_added' => 'Etiqueta agregada',
    ];

    const QUESTION_TYPES = [
        'rating_1_5' => 'Puntuacion 1-5',
        'rating_0_10' => 'Puntuacion 0-10',
        'multi_choice' => 'Seleccion multiple',
        'single_choice' => 'Seleccion unica',
        'open_text' => 'Texto libre',
        'boolean' => 'Si/No',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'questions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class, 'survey_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
