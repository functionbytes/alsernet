<?php

namespace Modules\Mailing\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailValidation extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'mails_email_validations';

    protected $fillable = [
        'email',
        'is_valid',
        'reason',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
    ];

    /**
     * Check if the email validation passed
     */
    public function isValid(): bool
    {
        return $this->is_valid === true;
    }

    /**
     * Scope to filter only valid email validations
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope to filter only invalid email validations
     */
    public function scopeInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    /**
     * Scope to filter validations for a specific email
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }
}
