<?php

namespace Modules\Mailing\Models;

use Modules\Mailing\Library\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class EmailLink extends Model
{
    use HasUid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'link',
    ];

    /**
     * Association with mailList through mail_list_id column.
     */
    public function email()
    {
        return $this->belongsTo('Acelle\Model\Email');
    }
}
