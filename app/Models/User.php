<?php

namespace App\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Traits\HasBasicRelations;
use Modules\Auth\Traits\HasUserAttributes;
use Modules\Auth\Traits\HasUserScopes;
use Modules\Core\Traits\HasQuotaManagement;
use Modules\Notification\Traits\HasNotificationSystem;
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
    use HasApiTokens, HasFactory, HasRoles, HasUid, LogsActivity;

    // Custom User traits organized by responsibility
    use HasBasicRelations;
    use HasFileSystemPaths;

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

    protected $quotaTracker;

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

    protected static $recordEvents = ['deleted', 'updated', 'created'];

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
        'role',
        'company',
        'detail',
        'user_img',
        'citie_id',
        'enterprise_id',
        'mail_verified_at',
        'remember_token',
        'timezone',
        'voilated',
        'last_login_at',
        'last_login_ip',
        'last_logins_at',
        'created_at',
        'updated_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }
}
