<?php

namespace App\Services\Return;

use App\Models\Return\ReturnRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\Return\ReturnConfirmationMail;
use App\Mail\Return\ReturnStatusUpdateMail;

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
