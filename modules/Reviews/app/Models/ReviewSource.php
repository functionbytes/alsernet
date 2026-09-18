<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una ficha de la que se leen opiniones: hoy, cada tienda física en Google.
 *
 * Las credenciales se guardan cifradas. Nunca se muestran de vuelta en el
 * formulario: se indica si están puestas y se pueden sustituir, pero no leer.
 */
class ReviewSource extends Model
{
    public const PLATFORM_GOOGLE = 'google';

    protected $connection = 'helpdesk';

    protected $table = 'review_sources';

    protected $guarded = ['id'];

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'active' => 'boolean',
        'auto_approve' => 'boolean',
        'last_fetch_at' => 'datetime',
        'fetched_total' => 'integer',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'source_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * ¿Tiene lo mínimo para poder leer reseñas?
     *
     * La API de Google Business Profile no admite una simple clave: hay que
     * autorizar la cuenta propietaria del negocio y guardar el refresh token
     * que devuelve.
     */
    public function isConfigured(): bool
    {
        $c = (array) $this->credentials;

        return filled($this->external_id)
            && filled($c['client_id'] ?? null)
            && filled($c['client_secret'] ?? null)
            && filled($c['refresh_token'] ?? null);
    }

    /** Qué falta por rellenar, para poder decirlo en la pantalla. */
    public function missingCredentials(): array
    {
        $c = (array) $this->credentials;
        $faltan = [];

        foreach (['client_id' => 'ID de cliente', 'client_secret' => 'Secreto de cliente', 'refresh_token' => 'Token de actualización'] as $clave => $etiqueta) {
            if (blank($c[$clave] ?? null)) {
                $faltan[] = $etiqueta;
            }
        }

        if (blank($this->external_id)) {
            $faltan[] = 'Identificador de la ficha';
        }

        return $faltan;
    }

    public function credential(string $key): ?string
    {
        return ((array) $this->credentials)[$key] ?? null;
    }
}
