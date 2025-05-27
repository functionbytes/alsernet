<?php

namespace App\Mail\Return;

use App\Models\Return\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReturnConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $return;

    public function __construct(ReturnRequest $return)
    {
        $this->return = $return;
    }

    public function build()
    {
        return $this->subject('Confirmación de Solicitud de Devolución #' . $this->return->id_return_request)
            ->view('emails.return-confirmation')
            ->with([
                'return' => $this->return,
                'customer_name' => $this->return->customer_name,
                'return_id' => $this->return->id_return_request,
                'order_id' => $this->return->id_order,
                'status' => $this->return->getStatusName(),
                'return_type' => $this->return->getReturnTypeName(),
                'logistics_mode' => $this->return->getLogisticsModeLabel(),
                'company_info' => config('returns.company_info')
            ]);
    }
}
