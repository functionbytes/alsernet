<?php

namespace Modules\Attention\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Attention\Database\Factories\AttentionNoteFactory;

/**
 * Notas internas privadas de un peticiones
 * No visibles para el ciudadano
 */
class AttentionNote extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return AttentionNoteFactory::new();
    }

    protected $table = 'attention_notes';

    protected $fillable = [
        'attention_id',
        'user_id',
        'content',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Attention this note belongs to
     */
    public function attention(): BelongsTo
    {
        return $this->belongsTo(Attention::class, 'attention_id');
    }

    /**
     * User who created the note
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope recent notes
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
