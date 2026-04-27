<?php

namespace Modules\Attention\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Attention\Database\Factories\AttentionActionFactory;

/**
 * Historial de acciones realizadas en un peticiones
 * Para trazabilidad y auditoría
 */
class AttentionAction extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return AttentionActionFactory::new();
    }

    protected $table = 'attention_actions';

    protected $fillable = [
        'attention_id',
        'user_id',
        'action',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Attention this action belongs to
     */
    public function attention(): BelongsTo
    {
        return $this->belongsTo(Attention::class, 'attention_id');
    }

    /**
     * User who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope recent actions
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope by action type
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
