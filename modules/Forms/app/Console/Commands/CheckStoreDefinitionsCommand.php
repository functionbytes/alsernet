<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormPrestashopPublication;
use Modules\Forms\Services\FormArtifactBuilder;
use Modules\Forms\Services\FormPublishingService;
use Modules\Forms\Services\PrestashopFormsClient;

/**
 * Comprueba que la tienda sirve lo que el panel cree haber publicado.
 *
 * El panel guarda el hash de lo último que envió, pero nunca preguntaba a la
 * tienda qué tiene de verdad. Si restauran PrestaShop de una copia de
 * seguridad, el editor sigue diciendo «publicado, sin cambios pendientes»
 * mientras el sitio sirve una versión vieja, y nadie se entera hasta que un
 * cliente se queja de un formulario que ya se había corregido.
 *
 * La acción `form.get` del api.php estaba implementada justo para esto y no la
 * llamaba nadie.
 */
class CheckStoreDefinitionsCommand extends Command
{
    protected $signature = 'forms:check-store
                            {--fix : Republica los formularios que no coincidan}';

    protected $description = 'Compara la definición publicada en la tienda con la del panel';

    public function handle(
        PrestashopFormsClient $client,
        FormArtifactBuilder $builder,
        FormPublishingService $publishing
    ): int {
        if (! $client->isConfigured()) {
            $this->error('La conexión con la tienda no está configurada.');

            return self::FAILURE;
        }

        $publicaciones = FormPrestashopPublication::with('form')->orderBy('form_key')->get();
        $filas = [];
        $descuadres = [];

        foreach ($publicaciones as $publicacion) {
            $form = $publicacion->form;

            if (! $form instanceof Form) {
                $filas[] = [$publicacion->form_key, '—', '—', 'sin formulario vinculado'];
                $descuadres[] = $publicacion;

                continue;
            }

            $respuesta = $client->fetch($publicacion->form_key);

            if (($respuesta['ok'] ?? false) !== true) {
                $filas[] = [
                    $publicacion->form_key,
                    substr((string) $publicacion->published_hash, 0, 8),
                    '—',
                    'la tienda no lo tiene: '.($respuesta['error'] ?? 'error'),
                ];
                $descuadres[] = $publicacion;

                continue;
            }

            $enTienda = (string) ($respuesta['data']['hash'] ?? '');
            $actual = $builder->currentHash($form, [
                'form_key' => $publicacion->form_key,
                'recaptcha_site_key' => (string) config('forms.store.recaptcha_site_key', ''),
            ]);

            if ($enTienda === $actual) {
                $estado = 'al día';
            } elseif ($enTienda === (string) $publicacion->published_hash) {
                // La tienda tiene lo último que se le envió; el desfase es que
                // alguien editó el formulario y no lo ha publicado todavía.
                $estado = 'cambios sin publicar';
            } else {
                $estado = 'DESCUADRE: la tienda sirve otra versión';
                $descuadres[] = $publicacion;
            }

            $filas[] = [
                $publicacion->form_key,
                substr($actual, 0, 8),
                substr($enTienda, 0, 8) ?: '—',
                $estado,
            ];
        }

        $this->table(['formulario', 'panel', 'tienda', 'estado'], $filas);

        if ($descuadres === []) {
            $this->info('Todo cuadra.');

            return self::SUCCESS;
        }

        $this->warn(count($descuadres).' formulario(s) no coinciden con la tienda.');

        if (! $this->option('fix')) {
            $this->line('Vuelve a lanzarlo con --fix para republicarlos.');

            return self::FAILURE;
        }

        foreach ($descuadres as $publicacion) {
            if (! $publicacion->form) {
                continue;
            }

            $publishing->publish($publicacion->form, null, true)
                ? $this->info('  republicado '.$publicacion->form_key)
                : $this->error('  no se pudo republicar '.$publicacion->form_key);
        }

        return self::SUCCESS;
    }
}
