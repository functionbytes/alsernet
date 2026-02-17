<?php

namespace Modules\Template\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Template\Traits\Versionable;

class Template extends Model
{
    use HasFactory, Versionable;

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
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relación: Template padre (herencia)
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'inherit', 'slug');
    }

    /**
     * Relación: Templates hijos que heredan de este
     */
    public function children()
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
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
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
        $current = $this;

        while ($current && $current->inherit) {
            $current = $current->parent;
            if ($current) {
                $chain[] = $current;
            }
        }

        return $chain;
    }
}
