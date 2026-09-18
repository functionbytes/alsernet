<?php

namespace Modules\Helpdesk\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_skills';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Users who have this skill (cross-DB: user_id references the default DB).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'helpdesk_user_skills',
            'skill_id',
            'user_id'
        )->withPivot('proficiency');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'helpdesk_conversation_skills',
            'skill_id',
            'conversation_id'
        );
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('slug', 'like', "%{$term}%");
    }
}
