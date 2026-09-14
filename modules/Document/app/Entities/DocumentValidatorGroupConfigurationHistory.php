<?php

namespace Modules\Document\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentValidatorGroupConfigurationHistory extends Model
{
    protected $table = 'document_validator_group_configuration_histories';

    protected $fillable = [
        'validator_group_id',
        'configuration_id',
        'user_id',
        'configuration_key',
        'configuration_label',
        'old_value',
        'new_value',
        'action',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'boolean',
            'new_value' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DocumentValidatorGroup::class, 'validator_group_id');
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(DocumentValidatorGroupConfiguration::class, 'configuration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create' => 'Creada',
            'update' => 'Actualizada',
            'delete' => 'Eliminada',
            default => $this->action,
        };
    }

    public function getChangeDescriptionAttribute(): string
    {
        if ($this->action === 'create') {
            return "Habilitada por {$this->user?->firstname} {$this->user?->lastname}";
        }

        $valueText = $this->new_value ? 'Habilitada' : 'Deshabilitada';

        return "$valueText por {$this->user?->firstname} {$this->user?->lastname}";
    }
}
