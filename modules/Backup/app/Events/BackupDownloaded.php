<?php

namespace Modules\Backup\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupDownloaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $filename,
        public readonly ?User $actor = null,
    ) {}
}
