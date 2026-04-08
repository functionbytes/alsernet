<?php

namespace Modules\Helpdesk\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    use HasUid;

    protected $table = 'helpdesk_ticket_attachments';

    protected $fillable = [
        'uid',
        'ticket_message_id',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Message this attachment belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'ticket_message_id');
    }

    /**
     * Get the URL to the attachment
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::url($this->path),
        );
    }

    /**
     * Get the size in KB
     */
    protected function sizeInKb(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->size / 1024, 2),
        );
    }
}
