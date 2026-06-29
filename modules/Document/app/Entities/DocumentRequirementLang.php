<?php

namespace Modules\Document\Entities;

use App\Models\Lang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Document\Traits\HasUid;

class DocumentRequirementLang extends Model
{
    use HasUid;

    protected $table = 'document_type_requirement_langs';

    protected $fillable = [
        'uid',
        'document_requirement_id',
        'lang_id',
        'name',
        'help_text',
    ];

    public function documentRequirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class);
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Lang::class);
    }
}
