<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MailingGroup extends Model
{
    use HasFactory;

    protected $table = 'mails_mailrelay_groups';

    protected $fillable = [
        'mailrelay_group_id',
        'name',
        'description',
        'subscriber_count',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(Subscriber::class, 'subscriber_mailrelay_group');
    }

    public function syncSubscriberCount(): void
    {
        $this->update([
            'subscriber_count' => $this->subscribers()->count(),
        ]);
    }
}
