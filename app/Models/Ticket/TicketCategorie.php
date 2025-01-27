<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Models\Group\GroupCategorie;
use App\Models\User;



class TicketCategorie extends Model
{
    use HasFactory;

    protected $table = 'ticket_categories';

    protected $fillable = [
        'slack',
        'title',
        'slug',
        'available',
        'priority_id',
        'created_at',
        'updated_at'
    ];

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }
    public function scopeSlug($query ,$slug)
    {
        return $query->where('slug', $slug)->first();
    }
    public function scopeSlack($query ,$slack)
    {
        return $query->where('slack', $slack)->first();
    }
    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }
    public function tickets(): hasMany
    {
        return $this->hasMany('App\Models\Ticket\Ticket', 'category_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo('App\Models\Ticket\Priority', 'priority_id');
    }

    public function user(){
        return $this->hasMany(User::class);
    }

    public function groupscategory()
    {
        return $this->belongsToMany(GroupCategorie::class, 'groups_categories','category_id','group_id');
    }

    public function groupscategoryc()
    {
        return $this->hasMany(GroupCategorie::class,'category_id');
    }

}
