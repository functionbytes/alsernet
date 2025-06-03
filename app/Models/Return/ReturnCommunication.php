<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnCommunication extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id',
        'type',
        'recipient',
        'subject',
        'content',
        'template_used',
        'status',
        'sent_at',
        'read_at',
        'sent_by',
        'metadata'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Constantes
    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';
    const TYPE_INTERNAL = 'internal_note';

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_READ = 'read';

    // Relaciones
    public function return()
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest');
    }

    // Scopes
    public function scopeEmails($query)
    {
        return $query->where('type', self::TYPE_EMAIL);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // Métodos
    public function markAsSent()
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now()
        ]);
    }

    public function markAsFailed($reason = null)
    {
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $reason;

        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata
        ]);
    }

    public function markAsRead()
    {
        $this->update([
            'status' => self::STATUS_READ,
            'read_at' => now()
        ]);
    }
}

