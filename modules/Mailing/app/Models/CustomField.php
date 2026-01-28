<?php

namespace Modules\Mailing\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use HasFactory;

    protected $table = 'mails_custom_fields';

    protected $fillable = [
        'name',
        'type',
    ];

    public function subscribers()
    {
        return $this->belongsToMany(Subscriber::class, 'subscriber_custom_fields');
    }
}
