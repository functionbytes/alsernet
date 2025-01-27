<?php

namespace App\Models\Livechat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveChatReviews extends Model
{
    use HasFactory;


    public  $table = 'livechat_reviews';

    protected $fillable = [
        'users_id',
        'cust_id',
        'starRating',
        'problemRectified',
        'feedBackData',
    ];
}
