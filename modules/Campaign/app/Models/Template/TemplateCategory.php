<?php

namespace Modules\Campaign\Models\Template;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class TemplateCategory extends Model
{
    use HasUid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The template that belong to the categories.
     */
    public function templates()
    {
        return $this->belongsToMany('Modules\Campaign\Models\Template\Template', 'templates_categories', 'category_id', 'template_id');
    }
}
