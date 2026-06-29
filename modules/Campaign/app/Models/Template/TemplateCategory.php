<?php

namespace Modules\Campaign\Models\Template;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Model;

class TemplateCategory extends Model
{
    protected $table = 'campaign_template_categories';

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
        return $this->belongsToMany('Modules\Campaign\Models\Template\Template', 'campaign_templates_categories', 'category_id', 'template_id');
    }
}
