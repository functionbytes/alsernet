<?php

namespace Modules\Template\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Template\Database\Factories\TemplateFactory;
use Modules\Template\Traits\Versionable;

class Template extends Model
{
    use HasFactory, SoftDeletes, Versionable;

    protected static function newFactory(): TemplateFactory
    {
        return TemplateFactory::new();
    }

    protected $table = 'templates';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'content',
        'template_path',
        'inherit',
        'status',
        'user_id',
        'author',
        'version',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Usuario creador
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Template padre (herencia)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'inherit', 'slug');
    }

    /**
     * Relación: Templates hijos que heredan de este
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'inherit', 'slug');
    }

    /**
     * Obtener la versión más reciente
     */
    public function getLatestVersion(): ?TemplateVersion
    {
        return $this->versions()->latest()->first();
    }

    /**
     * Scope: Templates activos
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Búsqueda por nombre o descripción
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term): void {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * Verificar si es el template activo
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Obtener información de herencia
     */
    public function getInheritanceChain(): array
    {
        $chain = [];
        $visited = [];
        $current = $this;

        while ($current && $current->inherit) {
            if (in_array($current->slug, $visited, true)) {
                break;
            }
            $visited[] = $current->slug;
            $current = $current->parent;
            if ($current) {
                $chain[] = $current;
            }
        }

        return $chain;
    }
}
