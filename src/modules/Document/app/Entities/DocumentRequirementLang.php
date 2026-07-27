<?php

namespace Modules\Document\Entities;

use App\Models\Lang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequirementLang extends Model
{
    protected $table = 'document_type_requirement_langs';

    protected $fillable = [
        'document_type_requirement_id',
        'lang_id',
        'name',
        'description',
        'help_text',
    ];

    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class, 'document_type_requirement_id');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Lang::class);
    }
}
