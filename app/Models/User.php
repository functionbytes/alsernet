<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens , LogsActivity;

    protected $table = 'users';
    protected static $recordEvents = ['deleted','updated','created'];

    protected $fillable = [
        'slack',
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
        'email_verified_at',
        'remember_token',
        'timezone',
        'voilated',
        'verified',
        'last_login_at',
        'last_login_ip',
        'last_logins_at',
        'created_at',
        'updated_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $dates = [
        'last_login_at',
        'deleted_at'
    ];

    protected $appends = ['full_name', 'image'];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $casts = [
        'active' => 'boolean',
        'confirmed' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {

        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logFillable()
            ->setDescriptionForEvent(fn(string $eventName) => "This model has been {$eventName}");

    }


    public function scopeAvailable($query)
    {
        return $query->where('users.available', 1);
    }


    public function redirect()
    {
        if ($this->hasRole('manager')) {
            return 'manager.dashboard';
        }

        if ($this->hasRole('inventarie')) {
            return 'inventarie.dashboard';
        }

        return 'login';
    }

    public function type()
    {
        if ($this->hasRole('manager')) {
            return 'Administrador';
        }

        if ($this->hasRole('inventarie')) {
            return 'Inventario';
        }

        return '';
    }

    public function role()
    {
        if ($this->hasRole('manager')) {
            return 'manager';
        }

        if ($this->hasRole('inventarie')) {
            return 'inventarie';
        }

        return '';
    }


    public function passwordHistories(): HasMany
    {
        return $this->hasMany('App\Models\Setting\PasswordHistory','user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany('App\Models\Setting\Session','user_id');
    }


    public function orders(): HasMany
    {
        return $this->hasMany('App\Models\Order\Order','user_id');
    }

    public function hasRole($role) {
        return $this->roles->contains('title', $role);
    }

    public static function auth(){

        return Auth::user();
    }


    public function session() : HasOne
    {
        return $this->hasOne('App\Models\Setting\Session');
    }

    public function scopeValidationsEmail($query,$email)
    {
        return $query->where('email', $email)->get();
    }

    public function scopeValidationEmail($query,$email)
    {
        return $query->where('email', $email)->first();
    }

    public function scopeValidations($query )
    {
        return $query->where('slack', null)->get();
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeSlack($query ,$slack)
    {
        return $query->where('slack', $slack)->first();
    }

    public function scopeEmail($query ,$email)
    {
        return $query->where('email', $email)->first();
    }

    public static function existence($slack){
        return User::where("slack", '=', $slack)->first();
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'role_user');
    }

    public function getRole()
    {
        return $this->roles()->first();
    }

    public function getRoleName()
    {
        return $this->roles()->first()->name ?? 'Sin rol';
    }



}

