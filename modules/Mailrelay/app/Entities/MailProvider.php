<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Configuración de providers de email (Mailrelay, Mailtrap, SendGrid, etc.)
 *
 * @property int $id
 * @property string $name Nombre visible del provider
 * @property string $driver Driver del provider (mailrelay, mailtrap, etc.)
 * @property array $credentials Credenciales (api_key, api_url, etc.)
 * @property bool $is_active Si el provider está activo
 * @property bool $is_default Si es el provider por defecto
 * @property int $priority Prioridad para failover (mayor = más prioridad)
 * @property array|null $limits Rate limits personalizados
 * @property array|null $metadata Configuración adicional
 * @property \Carbon\Carbon|null $last_tested_at Última vez que se testeó
 * @property bool $connection_ok Si la última prueba de conexión fue exitosa
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class MailProvider extends Model
{
    protected $table = 'mail_providers';

    protected $fillable = [
        'name',
        'driver',
        'credentials',
        'is_active',
        'is_default',
        'priority',
        'limits',
        'metadata',
        'last_tested_at',
        'connection_ok',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array', // Encriptar credenciales sensibles
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'limits' => 'array',
        'metadata' => 'array',
        'connection_ok' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_default' => false,
        'priority' => 0,
        'connection_ok' => false,
    ];

    /**
     * Relación con campaigns que usan este provider
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'mail_provider_id');
    }

    /**
     * Scope: Solo providers activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Provider por defecto
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true)->where('is_active', true);
    }

    /**
     * Scope: Ordenar por prioridad
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Al marcar como default, desmarcar otros
        static::saving(function ($provider) {
            if ($provider->is_default && $provider->is_active) {
                static::where('id', '!=', $provider->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        // Si no hay ningún default y este es el primero activo, marcarlo como default
        static::created(function ($provider) {
            if ($provider->is_active) {
                $hasDefault = static::where('is_default', true)
                    ->where('is_active', true)
                    ->exists();

                if (! $hasDefault) {
                    $provider->update(['is_default' => true]);
                }
            }
        });
    }

    /**
     * Verificar si el provider está funcionando
     */
    public function isWorking(): bool
    {
        return $this->is_active && $this->connection_ok;
    }

    /**
     * Obtener información del driver
     */
    public function getDriverInfo(): array
    {
        $driverInfo = [
            'mailrelay' => [
                'name' => 'Mailrelay',
                'icon' => 'fas fa-envelope-open-text',
                'color' => 'primary',
            ],
            'mailtrap' => [
                'name' => 'Mailtrap',
                'icon' => 'fas fa-inbox',
                'color' => 'success',
            ],
            'sendgrid' => [
                'name' => 'SendGrid',
                'icon' => 'fas fa-paper-plane',
                'color' => 'info',
            ],
            'aws_ses' => [
                'name' => 'AWS SES',
                'icon' => 'fab fa-aws',
                'color' => 'warning',
            ],
            'postmark' => [
                'name' => 'Postmark',
                'icon' => 'fas fa-stamp',
                'color' => 'danger',
            ],
        ];

        return $driverInfo[$this->driver] ?? [
            'name' => ucfirst($this->driver),
            'icon' => 'fas fa-server',
            'color' => 'secondary',
        ];
    }

    /**
     * Obtener label con badge de estado
     */
    public function getStatusBadge(): string
    {
        if (! $this->is_active) {
            return '<span class="badge bg-secondary">Inactivo</span>';
        }

        if ($this->connection_ok) {
            return '<span class="badge bg-success">Conectado</span>';
        }

        if ($this->last_tested_at) {
            return '<span class="badge bg-warning">Desconectado</span>';
        }

        return '<span class="badge bg-info">No testeado</span>';
    }

    /**
     * Obtener descripción para UI
     */
    public function getDescription(): string
    {
        $info = $this->getDriverInfo();
        $status = $this->is_active ? 'Activo' : 'Inactivo';
        $default = $this->is_default ? ' (Por defecto)' : '';

        return "{$info['name']} - {$status}{$default}";
    }
}
