<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;
use App\Library\Traits\HasUid;

class TemplateCategory extends Model
{
    use HasUid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * The template that belong to the categories.
     */
    public function templates()
    {
        return $this->belongsToMany('App\Models\Template\Template', 'templates_categories', 'category_id', 'template_id');
    }
}
