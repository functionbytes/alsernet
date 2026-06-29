<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'mails_groups';

    protected $fillable = [
        'name',
        'list_id',
    ];

    public function list()
    {
        return $this->belongsTo(Lists::class);
    }

    public function subscribers()
    {
        return $this->hasMany(Subscriber::class);
    }
}
