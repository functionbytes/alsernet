<?php

namespace App\Models\Group;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupCategorie extends Model
{
    use HasFactory;

    protected $table = 'ticket_groups_categories';

    protected $fillable = [
        'group_id',
        'categorie_id',
    ];

    public function groups()
    {
        return $this->belongsTo('App\Models\Group\Group', 'group_id', 'id');
    }

}
