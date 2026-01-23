<?php

namespace Modules\Auth\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasUserScopes
 *
 * Define query scopes reutilizables para el modelo User,
 * facilitando la búsqueda y filtrado de usuarios.
 */
trait HasUserScopes
{
    /**
     * Scope para usuarios disponibles
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('users.available', 1);
    }

    /**
     * Scope para validar múltiples emails
     */
    public function scopeValidationsEmail(Builder $query, string $email)
    {
        return $query->where('email', $email)->get();
    }

    /**
     * Scope para validar un email único
     */
    public function scopeValidationEmail(Builder $query, string $email)
    {
        return $query->where('email', $email)->first();
    }

    /**
     * Scope para usuarios sin validar (sin uid)
     */
    public function scopeValidations(Builder $query)
    {
        return $query->where('uid', null)->get();
    }

    /**
     * Scope para buscar por ID
     */
    public function scopeId(Builder $query, int $id)
    {
        return $query->where('id', $id)->first();
    }

    /**
     * Scope para buscar por UID
     */
    public function scopeUid(Builder $query, string $uid)
    {
        return $query->where('uid', $uid)->first();
    }

    /**
     * Scope para buscar por email
     */
    public function scopeEmail(Builder $query, string $email)
    {
        return $query->where('email', $email)->first();
    }

    /**
     * Scope para filtrar usuarios por parámetros
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            match ($key) {
                'status' => $query->where('status', $value),
                'email' => $query->where('email', 'like', "%{$value}%"),
                'firstname' => $query->where('firstname', 'like', "%{$value}%"),
                'lastname' => $query->where('lastname', 'like', "%{$value}%"),
                default => null,
            };
        }

        return $query;
    }

    /**
     * Scope para buscar usuarios por palabras clave
     */
    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        if (! empty(trim($keyword))) {
            return $query->where(function ($q) use ($keyword) {
                foreach (explode(' ', trim($keyword)) as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->orWhere('firstname', 'like', '%'.$term.'%')
                            ->orWhere('lastname', 'like', '%'.$term.'%')
                            ->orWhere('email', 'like', '%'.$term.'%');
                    });
                }
            });
        }

        return $query;
    }

    /**
     * Scope para filtrar por plan
     */
    public static function scopeByPlan(Builder $query, $plan): void
    {
        $query->whereHas('subscriptions', function ($q) use ($plan) {
            $q->newOrActive()->where('plan_id', '=', $plan->id);
        });
    }
}
