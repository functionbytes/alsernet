<?php

namespace Modules\Mailrelay\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'mails_email_templates';

    protected $fillable = [
        'name',
        'html_content',
        'text_content',
        'subject',
        'layout_id',
        'use_layout',
    ];

    protected function casts(): array
    {
        return [
            'layout_id' => 'integer',
            'use_layout' => 'boolean',
        ];
    }

    /**
     * Get the layout for this template.
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(MailrelayLayout::class, 'layout_id');
    }

    /**
     * Get the variables associated with this template.
     */
    public function variables(): BelongsToMany
    {
        return $this->belongsToMany(
            MailrelayVariable::class,
            'email_template_variables',
            'email_template_id',
            'mailrelay_variable_id'
        );
    }
}
