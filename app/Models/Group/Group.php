<?php

namespace App\Models\Group;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'ticket_groups';

    protected $fillable = [
        'uid',
        'title',
        'slug',
        'available',
        'created_at',
        'updated_at'
    ];

    public function scopeDescending($query)
{
    return $query->orderBy('created_at', 'desc');
}

public function scopeAscending($query)
{
    return $query->orderBy('created_at', 'asc');
}

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeSlug($query ,$slug)
    {
        return $query->where('slug', $slug)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', 'ticket_groups_users', 'group_id', 'user_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Ticket\TicketCategorie', 'ticket_groups_categories', 'group_id', 'category_id');
    }

    public function user()
    {
        return $this->hasMany('App\Models\Group\GroupUser', 'group_id');
    }

    public function categorie()
    {
        return $this->hasMany('App\Models\Group\GroupCategorie', 'group_id');
    }

}
