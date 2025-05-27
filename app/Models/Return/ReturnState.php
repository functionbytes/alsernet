<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnState extends Model
{
    protected $table = 'return_states';
    protected $primaryKey = 'id_return_state';

    protected $fillable = ['name'];

    public function statuses(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnStatus', 'id_return_state', 'id_return_state');
    }

    // Constantes para los estados principales
    const STATE_NEW = 1;
    const STATE_VERIFICATION = 2;
    const STATE_NEGOTIATION = 3;
    const STATE_RESOLVED = 4;
    const STATE_CLOSE = 5;
}
