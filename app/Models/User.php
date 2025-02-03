<?php

namespace App\Models;

use App\Http\Filters\V1\QueryFilter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens , LogsActivity , HasRoles;

    protected $table = 'users';
    protected static $recordEvents = ['deleted','updated','created'];

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
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }
        if ($user->hasRole('managers')) {
            return redirect()->route('manager.dashboard');
        }

        if ($user->hasRole('inventaries')) {
            return redirect()->route('inventarie.dashboard');
        }

        if ($user->hasRole('shops')) {
            return redirect()->route('shop.dashboard');
        }


        if ($user->hasRole('callcenters')) {
            return redirect()->route('callcenter.dashboard');
        }


        if ($user->hasRole('supports')) {
            return redirect()->route('support.dashboard');
        }


        return redirect()->route('login');
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
        return $query->where('uid', null)->get();
    }

    public function scopeId($query ,$id)
    {
        return $query->where('id', $id)->first();
    }

    public function scopeUid($query ,$uid)
    {
        return $query->where('uid', $uid)->first();
    }

    public function scopeEmail($query ,$email)
    {
        return $query->where('email', $email)->first();
    }

    public static function existence($uid){
        return User::where("uid", '=', $uid)->first();
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }


    public function getLanguageCode()
    {
        return $this->language ? $this->language->code : null;
    }

    public function getLanguageCodeFull()
    {
        $region_code = $this->language->region_code ? strtoupper($this->language->region_code) : strtoupper($this->language->code);
        return $this->language ? ($this->language->code.'-'.$region_code) : null;
    }



    public function language()
    {
        return $this->belongsTo('App\Models\Lang');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo('App\Models\Shop','shop_id','id');
    }

    public function scopeFilter(Builder $builder, QueryFilter $filters) {
        return $filters->apply($builder);
    }

    public function tickets() : HasMany {
        return $this->hasMany('App\Models\Ticket\Ticket');
    }

    public function getFullNameAttribute()
    {
        return "{$this->firstname} {$this->lastname}";
    }

    public function getImageAttribute()
    {
        return asset('images/default-user.png');
    }

}

