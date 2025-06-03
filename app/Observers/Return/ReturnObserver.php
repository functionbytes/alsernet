<?php

namespace App\Observers\Return;

use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnStatusHistory;

class ReturnObserver
{
    public function updating(ReturnRequest $return)
    {
        // Registrar cambio de estado
        if ($return->isDirty('status')) {
            ReturnStatusHistory::create([
                'return_id' => $return->id,
                'previous_status' => $return->getOriginal('status'),
                'new_status' => $return->status,
                'changed_by' => auth()->user()->name ?? 'Sistema',
                'notes' => $return->status_notes ?? null,
                'metadata' => [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toIso8601String()
                ]
            ]);
        }
}
}

