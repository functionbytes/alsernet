<?php

namespace Modules\Helpdesk\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model
{
    use HasUid, SoftDeletes;

    protected $table = 'helpdesk_statuses';

    protected $fillable = [
        'uid',
        'name',
        'slug',
        'type',
        'color',
        'order',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'order' => 'integer',
        ];
    }

    /**
     * Tickets using this status
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }
}
