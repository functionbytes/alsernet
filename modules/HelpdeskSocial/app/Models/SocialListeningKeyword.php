<?php

namespace Modules\HelpdeskSocial\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialListeningKeyword extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_social_listening_keywords';

    protected $fillable = [
        'keyword',
        'platforms',
        'match_type',
        'sentiment_filter',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
