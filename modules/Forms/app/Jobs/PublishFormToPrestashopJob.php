<?php

namespace Modules\Forms\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormPrestashopPublication;
use Modules\Forms\Services\FormArtifactBuilder;
use Modules\Forms\Services\PrestashopFormsClient;

/**
 * Compila el formulario y lo envía a la tienda.
 *
 * Va en la cola 'webhooks', que ya consume webadmin-worker-helpdesk. Mismos
 * reintentos que SendFormWebhookJob: la tienda puede estar reiniciándose y no
 * es motivo para dar la publicación por perdida.
 */
class PublishFormToPrestashopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300, 900];

    public function __construct(
        public int $formId,
        public ?int $userId = null,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(
        FormArtifactBuilder $builder,
        PrestashopFormsClient $client,
    ): void {
        $form = Form::with(['fields' => fn ($q) => $q->visible()->ordered()])->find($this->formId);

        if (! $form) {
            Log::warning('Forms: publicación cancelada, el formulario ya no existe.', ['form_id' => $this->formId]);

            return;
        }

        $publication = FormPrestashopPublication::firstOrNew(['form_id' => $form->id]);

        if (! $publication->form_key) {
            $publication->markFailed('El formulario no tiene form_key asignada.');

            return;
        }

        $artifact = $builder->build($form, [
            'form_key' => $publication->form_key,
            'recaptcha_site_key' => (string) config('forms.store.recaptcha_site_key', ''),
        ]);

        // La misma publicación reintentada no debe duplicar nada en la tienda.
        $idempotencyKey = 'form-publish-'.$form->id.'-'.substr($artifact['hash'], 0, 16);

        $response = $client->publish($artifact, $idempotencyKey);

        if (($response['ok'] ?? false) !== true) {
            $error = (string) ($response['error'] ?? 'respuesta inesperada de la tienda');

            // Mientras queden reintentos se deja el estado como está: marcarlo
            // 'failed' en el primer intento haría parpadear el aviso en la UI.
            if ($this->attempts() < $this->tries) {
                throw new \RuntimeException('Forms: la tienda rechazó la publicación: '.$error);
            }

            $publication->markFailed($error);

            return;
        }

        $publication->markPublished($artifact['hash'], $this->userId);

        Log::info('Forms: formulario publicado en la tienda.', [
            'form_id' => $form->id,
            'form_key' => $publication->form_key,
            'version' => $publication->published_version,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        FormPrestashopPublication::where('form_id', $this->formId)
            ->first()?->markFailed($e->getMessage());
    }
}
