<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSession extends Model
{
    use HasFactory;

    protected $table = 'helpdesk_customer_sessions';

    protected $fillable = [
        'customer_id',
        'ip_address',
        'user_agent',
        'country',
        'city',
        'latitude',
        'longitude',
        'session_token',
        'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the customer that owns this session
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get device type from user agent
     */
    public function getDeviceTypeAttribute()
    {
        if (! $this->user_agent) {
            return 'Unknown';
        }

        if (stripos($this->user_agent, 'mobile') !== false || stripos($this->user_agent, 'android') !== false) {
            return 'Mobile';
        }

        if (stripos($this->user_agent, 'tablet') !== false || stripos($this->user_agent, 'ipad') !== false) {
            return 'Tablet';
        }

        return 'Desktop';
    }

    /**
     * Get browser name from user agent
     */
    public function getBrowserAttribute()
    {
        if (! $this->user_agent) {
            return 'Unknown';
        }

        if (stripos($this->user_agent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (stripos($this->user_agent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (stripos($this->user_agent, 'Safari') !== false) {
            return 'Safari';
        } elseif (stripos($this->user_agent, 'Edge') !== false) {
            return 'Edge';
        }

        return 'Other';
    }

    /**
     * Update last activity time
     */
    public function updateActivity()
    {
        $this->update(['last_activity_at' => now()]);

        return $this;
    }

    /**
     * Check if session is still active (less than 30 minutes old)
     */
    public function isActive()
    {
        return $this->last_activity_at && $this->last_activity_at->diffInMinutes(now()) < 30;
    }
}
