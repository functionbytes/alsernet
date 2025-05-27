<?php


namespace App\Jobs\Return;

use App\Models\Return\ReturnRequest;
use App\Mail\Return\ReturnConfirmationMail;
use App\Mail\Return\ReturnStatusUpdateMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendReturnNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $returnRequest;
    protected $emailType;
    protected $additionalData;

    public $tries = 5;
    public $timeout = 60;
    public $backoff = [5, 15, 30, 60, 120];

    const TYPE_CONFIRMATION = 'confirmation';
    const TYPE_STATUS_UPDATE = 'status_update';
    const TYPE_ADMIN_NOTIFICATION = 'admin_notification';

    public function __construct(ReturnRequest $returnRequest, string $emailType, array $additionalData = [])
    {
        $this->returnRequest = $returnRequest;
        $this->emailType = $emailType;
        $this->additionalData = $additionalData;
        $this->onQueue('emails'); // Cola específica para emails
    }

    public function handle()
    {
        Log::info('Enviando email de devolución', [
            'return_id' => $this->returnRequest->id_return_request,
            'email_type' => $this->emailType,
            'recipient' => $this->returnRequest->email
        ]);

        try {
            switch ($this->emailType) {
                case self::TYPE_CONFIRMATION:
                    $this->sendConfirmationEmail();
                    break;

                case self::TYPE_STATUS_UPDATE:
                    $this->sendStatusUpdateEmail();
                    break;

                case self::TYPE_ADMIN_NOTIFICATION:
                    $this->sendAdminNotification();
                    break;

                default:
                    throw new \InvalidArgumentException("Tipo de email no válido: {$this->emailType}");
            }

            Log::info('Email enviado exitosamente', [
                'return_id' => $this->returnRequest->id_return_request,
                'email_type' => $this->emailType
            ]);

        } catch (\Exception $e) {
            Log::error('Error enviando email', [
                'return_id' => $this->returnRequest->id_return_request,
                'email_type' => $this->emailType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function sendConfirmationEmail()
    {
        Mail::to($this->returnRequest->email)
            ->send(new ReturnConfirmationMail($this->returnRequest));
    }

    private function sendStatusUpdateEmail()
    {
        Mail::to($this->returnRequest->email)
            ->send(new ReturnStatusUpdateMail($this->returnRequest));
    }

    private function sendAdminNotification()
    {
        $adminEmail = config('returns.notifications.admin_email');

        if ($adminEmail) {
            Mail::to($adminEmail)
                ->send(new \App\Mail\AdminReturnNotificationMail(
                    $this->returnRequest,
                    $this->additionalData
                ));
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Email job falló definitivamente', [
            'return_id' => $this->returnRequest->id_return_request,
            'email_type' => $this->emailType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Opcional: Registrar el fallo en la base de datos
        \DB::table('failed_email_notifications')->insert([
            'return_id' => $this->returnRequest->id_return_request,
            'email_type' => $this->emailType,
            'recipient' => $this->returnRequest->email,
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
            'attempts' => $this->attempts()
        ]);
    }

    public function retryUntil()
    {
        return now()->addHours(6); // Reintentar hasta 6 horas
    }

    public function uniqueId()
    {
        return "{$this->returnRequest->id_return_request}-{$this->emailType}";
    }
}
