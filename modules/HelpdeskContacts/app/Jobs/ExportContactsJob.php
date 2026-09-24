<?php

namespace Modules\HelpdeskContacts\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactCsvWriter;
use Throwable;

/**
 * Exportación de contactos con más filas de las que se descargan al momento
 * (mockup "Exportar contactos": con más de 5.000 filas se envía por email).
 * Genera el CSV en el disco local y manda al agente un enlace firmado de 24 h.
 */
class ExportContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    /**
     * @param  array<int, int>  $customerIds
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $customerIds,
        public readonly bool $includeHealth,
        public readonly bool $includeExternal,
    ) {
        $this->onQueue('exports');
    }

    public function handle(ContactCsvWriter $writer): void
    {
        $user = User::find($this->userId);
        if (! $user || ! $user->email) {
            return;
        }

        $path = 'contacts-exports/'.$this->userId.'/contactos-'.now()->format('Y-m-d-His').'-'.Str::random(8).'.csv';
        $tmp = tmpfile();
        fwrite($tmp, "\xEF\xBB\xBF");

        $query = Customer::query()->whereIn('id', $this->customerIds)->latest('last_seen_at');
        $writer->write($tmp, $query, $this->includeHealth, $this->includeExternal);

        rewind($tmp);
        Storage::disk('local')->put($path, $tmp);
        fclose($tmp);

        $url = URL::temporarySignedRoute('contacts.export.file', now()->addDay(), ['path' => base64_encode($path)]);
        $count = count($this->customerIds);

        Mail::raw(
            "Tu exportación de {$count} contactos está lista.\n\nDescárgala aquí (enlace válido 24 horas):\n{$url}\n\nExportar datos personales queda auditado.",
            fn ($message) => $message->to($user->email)->subject("Exportación de {$count} contactos")
        );
    }

    public function failed(Throwable $e): void
    {
        Log::error('ExportContactsJob: la exportación por email falló', [
            'user_id' => $this->userId,
            'rows' => count($this->customerIds),
            'error' => $e->getMessage(),
        ]);
    }
}
