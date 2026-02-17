<?php

namespace Modules\Template\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TemplateVersion extends Model
{
    use HasFactory;

    protected $table = 'template_versions';

    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'version',
        'content',
        'changed_fields',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'changed_fields' => 'json',
        'created_at' => 'datetime',
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
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Acceso rápido al usuario (alias)
     */
    public function user()
    {
        return $this->createdBy();
    }
}
