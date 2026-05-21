<?php

namespace Modules\Remarketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailer\Models\MailerTemplate;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'remarketing_templates';

    protected $fillable = [
        'store_id',
        'visibility',
        'mailer_template_id',
        'layout_id',
        'name',
        'type',
        'subject',
        'preheader',
        'html_content',
        'json_content',
        'thumbnail_url',
        'is_global',
    ];

    protected function casts(): array
    {
        return [
            'json_content' => 'array',
            'is_global' => 'boolean',
            'mailer_template_id' => 'integer',
            'layout_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function mailerTemplate(): BelongsTo
    {
        return $this->belongsTo(MailerTemplate::class, 'mailer_template_id');
    }
}
