<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAccount extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_email_accounts';

    protected $fillable = [
        'name',
        'email',
        'imap_host',
        'imap_port',
        'imap_username',
        'imap_password',
        'imap_folder',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_from_name',
        'smtp_encryption',
        'last_sync_at',
        'is_active',
        'inbox_id',
    ];

    protected $hidden = ['imap_password', 'smtp_password'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'imap_port' => 'integer',
            'smtp_port' => 'integer',
            'last_sync_at' => 'datetime',
        ];
    }

    protected function imapPassword(): Attribute
    {
        return Attribute::make(
            get: fn (?string $v) => $v ? decrypt($v) : null,
            set: fn (?string $v) => $v ? encrypt($v) : null,
        );
    }

    protected function smtpPassword(): Attribute
    {
        return Attribute::make(
            get: fn (?string $v) => $v ? decrypt($v) : null,
            set: fn (?string $v) => $v ? encrypt($v) : null,
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasImap(): bool
    {
        return filled($this->imap_host) && filled($this->imap_username);
    }

    public function hasSmtp(): bool
    {
        return filled($this->smtp_host) && filled($this->smtp_username);
    }
}
