<?php

namespace Modules\Reviews\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\Reviews\Enums\ReportStatus;

class GeneratedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'report_type',
        'format',
        'filename',
        'filepath',
        'filters',
        'file_size',
        'status',
        'error_message',
        'completed_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'filters' => 'array',
            'file_size' => 'integer',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ReportStatus::Completed)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function isCompleted(): bool
    {
        return $this->status === ReportStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === ReportStatus::Failed;
    }

    public function isProcessing(): bool
    {
        return $this->status === ReportStatus::Processing;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getDownloadUrl(): string
    {
        return route('reviews.reports.download', $this->id);
    }

    public function getFileSizeFormatted(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }

    public function markAsCompleted(string $filepath, int $fileSize): void
    {
        $this->update([
            'filepath' => $filepath,
            'file_size' => $fileSize,
            'status' => ReportStatus::Completed,
            'completed_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => ReportStatus::Failed,
            'error_message' => $errorMessage,
        ]);
    }

    public function deleteFile(): bool
    {
        if ($this->filepath && Storage::disk('local')->exists($this->filepath)) {
            return Storage::disk('local')->delete($this->filepath);
        }

        return false;
    }
}
