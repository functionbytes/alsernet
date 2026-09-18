<?php

namespace Modules\Forms\Services;

use Illuminate\Support\Carbon;
use Modules\Forms\Jobs\PublishFormToPrestashopJob;
use Modules\Forms\Models\AlsernetForm;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormPrestashopPublication;

/**
 * Orquesta la publicación de un formulario en la tienda.
 *
 * La regla del proyecto es que el sitio solo cambia cuando alguien lo pide: aquí
 * no hay publicación automática al guardar. Editar deja el formulario "con
 * cambios sin publicar" y ya está.
 */
class FormPublishingService
{
    public function __construct(
        private FormArtifactBuilder $builder,
        private PrestashopFormsClient $client,
    ) {}

    /**
     * Estado de publicación para pintar la UI del editor.
     *
     * @return array{configured: bool, linked: bool, form_key: ?string, status: string,
     *               published_at: ?Carbon, published_version: int,
     *               has_pending_changes: bool, last_error: ?string}
     */
    public function status(Form $form): array
    {
        $publication = $this->publicationFor($form);
        $configured = $this->client->isConfigured();

        if (! $publication) {
            return [
                'configured' => $configured,
                'linked' => false,
                'form_key' => null,
                'status' => 'unlinked',
                'published_at' => null,
                'published_version' => 0,
                'has_pending_changes' => false,
                'overrides_legacy' => false,
                'last_error' => null,
            ];
        }

        $currentHash = $this->builder->currentHash($form, [
            'form_key' => $publication->form_key,
            'recaptcha_site_key' => (string) config('forms.store.recaptcha_site_key', ''),
        ]);

        return [
            'configured' => $configured,
            'linked' => true,
            'form_key' => $publication->form_key,
            'status' => $publication->status,
            'published_at' => $publication->published_at,
            'published_version' => $publication->published_version,
            'has_pending_changes' => ! $publication->matches($currentHash),
            'overrides_legacy' => (bool) $publication->overrides_legacy,
            'last_error' => $publication->last_error,
        ];
    }

    /**
     * Publica el formulario en la tienda. Devuelve false si no hay nada que
     * publicar.
     *
     * Se intenta en vivo, no por cola: es una sola llamada HTTP corta, quien
     * pulsa "Publicar" quiere saber ya si la tienda lo aceptó, y las otras dos
     * operaciones del panel (retirar y activar en el sitio) siempre fueron
     * síncronas.
     *
     * Encolarlo era además una trampa en la práctica: la cola `webhooks` la
     * sirve el mismo worker que atiende antes `helpdesk-erp-warming`, y
     * mientras esa siga produciendo trabajo el job no llega a ejecutarse nunca.
     * El botón se quedaba "pendiente" para siempre sin decir por qué.
     *
     * El job sigue existiendo como red de seguridad: si la tienda no contesta,
     * se encola con sus reintentos y su backoff.
     */
    public function publish(Form $form, ?int $userId = null, bool $force = false): bool
    {
        $publication = $this->publicationFor($form);

        if (! $publication || ! $publication->form_key) {
            return false;
        }

        $options = [
            'form_key' => $publication->form_key,
            'recaptcha_site_key' => (string) config('forms.store.recaptcha_site_key', ''),
        ];

        $artifact = $this->builder->build($form, $options);

        if (! $force && $publication->matches($artifact['hash'])) {
            return false;
        }

        $publication->forceFill(['status' => 'pending'])->save();

        $response = $this->client->publish(
            $artifact,
            'form-publish-'.$form->id.'-'.substr($artifact['hash'], 0, 16)
        );

        if (($response['ok'] ?? false) === true) {
            $publication->markPublished($artifact['hash'], $userId);

            return true;
        }

        // La tienda no contesta: que lo siga intentando la cola.
        PublishFormToPrestashopJob::dispatch($form->id, $userId);

        return true;
    }

    /**
     * Retira el formulario de la tienda. Es una llamada síncrona: quien lo
     * despublica quiere saber ya si dejó de estar visible.
     *
     * @return array{ok: bool, error?: string}
     */
    public function unpublish(Form $form): array
    {
        $publication = $this->publicationFor($form);

        if (! $publication || ! $publication->form_key) {
            return ['ok' => false, 'error' => 'El formulario no está vinculado a la tienda.'];
        }

        $response = $this->client->unpublish($publication->form_key);

        if (($response['ok'] ?? false) !== true) {
            $error = (string) ($response['error'] ?? 'respuesta inesperada de la tienda');
            $publication->markFailed($error);

            return ['ok' => false, 'error' => $error];
        }

        $publication->forceFill([
            'status' => 'unpublished',
            'published_hash' => null,
            'last_error' => null,
            'last_attempt_at' => now(),
        ])->save();

        return ['ok' => true];
    }

    /**
     * Hace que este formulario sustituya (o deje de sustituir) al que la tienda
     * tiene en código.
     *
     * Publicar no cambia lo que ve el cliente; esto sí. Se mantiene aparte a
     * propósito: así se puede publicar y revisar en la tienda antes de activarlo,
     * y volver atrás sin desplegar nada.
     *
     * @return array{ok: bool, error?: string}
     */
    public function setOverride(Form $form, bool $overrides): array
    {
        $publication = $this->publicationFor($form);

        if (! $publication || ! $publication->isLive()) {
            return ['ok' => false, 'error' => 'Publica el formulario antes de activarlo en el sitio.'];
        }

        $response = $this->client->setOverride($publication->form_key, $overrides);

        if (($response['ok'] ?? false) !== true) {
            return ['ok' => false, 'error' => (string) ($response['error'] ?? 'respuesta inesperada de la tienda')];
        }

        $publication->forceFill(['overrides_legacy' => $overrides])->save();

        return ['ok' => true];
    }

    /**
     * Vincula el formulario a una form_key del catálogo de alsernetforms.
     *
     * La form_key tiene que existir ya en `helpdesk_forms`: es la que determina
     * la categoría del ticket que abrirá cada envío, y darla de alta es una
     * decisión de configuración de Helpdesk, no del constructor.
     *
     * @return array{ok: bool, error?: string}
     */
    public function link(Form $form, string $formKey): array
    {
        $alsernetForm = AlsernetForm::where('form_key', $formKey)->first();

        if (! $alsernetForm) {
            return ['ok' => false, 'error' => "La clave «{$formKey}» no existe en el catálogo de formularios del sitio."];
        }

        $takenBy = FormPrestashopPublication::where('form_key', $formKey)
            ->where('form_id', '!=', $form->id)
            ->first();

        if ($takenBy) {
            return ['ok' => false, 'error' => "La clave «{$formKey}» ya la usa otro formulario."];
        }

        FormPrestashopPublication::updateOrCreate(
            ['form_id' => $form->id],
            ['form_key' => $formKey, 'status' => 'pending'],
        );

        $alsernetForm->forceFill(['form_id' => $form->id])->save();

        return ['ok' => true];
    }

    private function publicationFor(Form $form): ?FormPrestashopPublication
    {
        return FormPrestashopPublication::where('form_id', $form->id)->first();
    }
}
