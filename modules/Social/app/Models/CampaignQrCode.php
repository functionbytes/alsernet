<?php

namespace Modules\Social\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignQrCode extends Model
{
    protected $table = 'social_campaign_qr_codes';

    protected $fillable = [
        'campaign_id',
        'code',
        'url',
        'file_path',
        'scans_count',
        'last_scanned_at',
        'scan_data',
    ];

    protected function casts(): array
    {
        return [
            'scan_data' => 'array',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function getQrImageUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return asset('storage/'.$this->file_path);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('public.campaign.qr', ['code' => $this->code]);
    }

    public function hasBeenScanned(): bool
    {
        return $this->scans_count > 0;
    }
}
