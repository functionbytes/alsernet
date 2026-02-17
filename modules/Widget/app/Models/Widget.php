<?php

namespace Modules\Widget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Widget extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'widgets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'widget_id',
        'sidebar_id',
        'theme',
        'position',
        'data',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'position' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            Cache::forget('widgets');
        });

        static::deleted(function () {
            Cache::forget('widgets');
        });
    }

    /**
     * Scope to get active widgets
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope to get widgets by sidebar
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySidebar($query, string $sidebarId)
    {
        return $query->where('sidebar_id', $sidebarId);
    }

    /**
     * Scope to get widgets by theme
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTheme($query, string $theme)
    {
        return $query->where('theme', $theme);
    }

    /**
     * Scope to order by position
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('position', $direction);
    }

    /**
     * Get widget configuration
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function getConfig(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->data;
        }

        return data_get($this->data, $key, $default);
    }

    /**
     * Set widget configuration
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setConfig($key, $value = null)
    {
        $data = $this->data ?? [];

        if (is_array($key)) {
            $data = array_merge($data, $key);
        } else {
            data_set($data, $key, $value);
        }

        $this->data = $data;

        return $this;
    }
}
