<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lists extends Model
{
    use HasFactory;

    protected $table = 'mails_lists';

    protected $fillable = [
        'name',
        'description',
    ];

    public function subscribers()
    {
        return $this->hasMany(Subscriber::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
