<?php

namespace Modules\Returns\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Mail\Mail\Return\ReturnConfirmationMail;
use Modules\Mail\Mail\Return\ReturnStatusUpdateMail;
use Modules\Returns\Models\ReturnRequest;

class ReturnEmailService
{
    /**
     * Enviar confirmación de devolución
     */
    public function sendReturnConfirmation(ReturnRequest $return): void
    {
        if (config('returns.send_confirmation_email', true)) {
            Mail::to($return->email)->send(new ReturnConfirmationMail($return));
        }
    }

    /**
     * Enviar notificación de cambio de estado
     */
    public function sendStatusUpdateNotification(ReturnRequest $return): void
    {
        if (config('returns.send_status_update_email', true)) {
            Mail::to($return->email)->send(new ReturnStatusUpdateMail($return));
        }
    }
}
