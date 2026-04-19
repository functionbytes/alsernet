<?php

namespace App\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Traits\HasBasicRelations;
use Modules\Auth\Traits\HasUserAttributes;
use Modules\Auth\Traits\HasUserScopes;
use Modules\Core\Traits\HasQuotaManagement;
use Modules\Notification\Traits\HasNotificationSystem;
use Modules\Reviews\Traits\HasNotificationPreferences;
use Modules\Storage\Traits\HasFileSystemPaths;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 *
 * Modelo principal de usuario del sistema. Utiliza traits especializados
 * para organizar la funcionalidad por responsabilidades.
 */
class User extends Authenticatable
{
    // Core Laravel traits
    use HasApiTokens, HasFactory, HasRoles, HasUid, LogsActivity, SoftDeletes;

    // Custom User traits organized by responsibility
    use HasBasicRelations;
    use HasFileSystemPaths;
    use HasNotificationPreferences;

    // Notifiable and HasNotificationSystem - resolve method conflicts
    use HasNotificationSystem, Notifiable {
        HasNotificationSystem::routeNotificationFor insteadof Notifiable;
        Notifiable::routeNotificationFor as protected routeNotificationForNotifiable;
    }
    use HasQuotaManagement;
    use HasUserAttributes;
    use HasUserScopes;

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    */

    protected $table = 'users';

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_ACTIVE = 'active';

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    */

    protected static function recordEvents(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->setDescriptionForEvent(fn (string $eventName) => "This model has been {$eventName}");
    }

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'firstname',
        'lastname',
        'identification',
        'cellphone',
        'email',
        'password',
        'address',
        'available',
        'verified',
        'terms',
        'validation',
        'page',
        'setting',
        // 'role' excluded: Spatie roles are managed via assignRole()/syncRoles(), not mass-assignment
        'company',
        'detail',
        'user_img',
        'citie_id',
        'enterprise_id',
        'mail_verified_at',
        'remember_token',
        'timezone',
        'locale',
        'voilated',
        'last_login_at',
        'last_login_ip',
        'last_logins_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'failed_login_count',
        'locked_until',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    protected $appends = ['full_name', 'image'];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'mail_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'deleted_at' => 'datetime',
            'active' => 'boolean',
            'confirmed' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_secret' => 'encrypted',
            'locked_until' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
