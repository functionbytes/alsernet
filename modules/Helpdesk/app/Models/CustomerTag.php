<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Etiqueta libre aplicable a contactos (Contactos 360, mockup pieza "Editar
 * contacto" / filtro "Etiquetas" del listado). Compartida entre todos los
 * contactos del helpdesk, no por agente/bandeja.
 */
class CustomerTag extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_customer_tags';

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'helpdesk_customer_tag_pivot', 'tag_id', 'customer_id')
            ->withTimestamps();
    }

    /**
     * Find an existing tag by name (case-insensitive) or create one —
     * usado por el find-or-create del selector de etiquetas del modal Editar.
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);

        $existing = static::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'name' => $name,
            'slug' => static::uniqueSlug($name),
        ]);
    }

    private static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'etiqueta';
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
