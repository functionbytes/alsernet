<?php

namespace App\Models\Newsletter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class NewsletterList extends Model
{
    use HasFactory;

    protected $table = "newsletter_lists";

    protected $fillable = [
        'slack',
        'title',
        'code',
        'available',
        'lang_id',
        'created_at',
        'updated_at'
    ];


    public function scopeAvailable($query)
    {
        return $query->where('available', 1);
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeCode($query ,$code)
    {
        return $query->where('code', $code)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo('App\Models\Lang','lang_id','id');
    }

    public function newsletters(): HasMany
    {
        return $this->hasMany('App\Models\Newsletter\NewsletterListsUser', 'list_id', 'id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Newsletter\Newsletter', 'newsletter_lists_users', 'list_id', 'newsletter_id');
    }

}
