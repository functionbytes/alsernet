<?php

namespace Modules\Template\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Template\Database\Factories\TemplateVersionFactory;

class TemplateVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): TemplateVersionFactory
    {
        return TemplateVersionFactory::new();
    }

    protected $table = 'template_versions';

    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'version',
        'content',
        'description',
        'changed_fields',
        'created_by',
        'name',
        'slug',
        'author',
        'created_at',
        'deleted_at',
    ];

    protected $casts = [
        'changed_fields' => 'json',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación: Template padre
     */
    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Relación: Usuario que creó la versión
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Acceso rápido al usuario (alias)
     */
    public function user()
    {
        return $this->createdBy();
    }
}
