<?php

namespace Modules\Forms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'form_versions';

    protected $fillable = [
        'form_id',
        'version_number',
        'form_snapshot',
        'fields_snapshot',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'form_snapshot' => 'array',
            'fields_snapshot' => 'array',
            'version_number' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
