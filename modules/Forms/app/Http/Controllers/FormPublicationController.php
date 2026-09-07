<?php

namespace Modules\Forms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Forms\Models\AlsernetForm;
use Modules\Forms\Models\Form;
use Modules\Forms\Services\FormPublishingService;

/**
 * Publicar / despublicar un formulario en la tienda (PrestaShop).
 *
 * Publicar es lo único que hace llegar un cambio al sitio: guardar en el editor
 * nunca toca la tienda.
 */
class FormPublicationController extends Controller
{
    public function __construct(private FormPublishingService $publishing)
    {
        $this->middleware('can:Forms.forms.edit');
    }

    public function link(Request $request, Form $form): RedirectResponse
    {
        $validated = $request->validate([
            'form_key' => ['required', 'string', 'max:60'],
        ], [], ['form_key' => 'clave del formulario en el sitio']);

        $result = $this->publishing->link($form, $validated['form_key']);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', 'Formulario vinculado al sitio. Ya puedes publicarlo.');
    }

    public function publish(Form $form): RedirectResponse
    {
        $status = $this->publishing->status($form);

        if (! $status['linked']) {
            return back()->with('error', 'Antes de publicar, vincula el formulario a una clave del sitio.');
        }

        if (! $status['configured']) {
            return back()->with('error', 'La conexión con la tienda no está configurada (FORMS_STORE_API_URL).');
        }

        if (! $this->publishing->publish($form, $form->exists ? auth()->id() : null)) {
            return back()->with('info', 'No hay cambios que publicar: el sitio ya sirve esta versión.');
        }

        return back()->with('success', 'Publicando en el sitio. En unos segundos estará disponible.');
    }

    /**
     * Republica aunque el hash coincida. Para cuando la tienda se ha restaurado
     * desde una copia de seguridad y se ha quedado atrás sin que aquí conste.
     */
    public function republish(Form $form): RedirectResponse
    {
        if (! $this->publishing->publish($form, auth()->id(), force: true)) {
            return back()->with('error', 'El formulario no está vinculado a ninguna clave del sitio.');
        }

        return back()->with('success', 'Reenviando la definición al sitio.');
    }

    /**
     * Activa o desactiva que este formulario sustituya al que la tienda tiene
     * en código. Es el paso final de migrar un formulario, y el que se deshace
     * si algo va mal.
     */
    public function override(Request $request, Form $form): RedirectResponse
    {
        $overrides = $request->boolean('overrides_legacy');

        $result = $this->publishing->setOverride($form, $overrides);

        if (! $result['ok']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $overrides
            ? 'Este formulario ya es el que se muestra en el sitio.'
            : 'El sitio vuelve a mostrar el formulario antiguo.');
    }

    public function unpublish(Form $form): RedirectResponse
    {
        $result = $this->publishing->unpublish($form);

        if (! $result['ok']) {
            return back()->with('error', 'No se pudo retirar del sitio: '.$result['error']);
        }

        return back()->with('success', 'Formulario retirado del sitio.');
    }

    /**
     * Claves libres del catálogo, para el desplegable de vinculación.
     */
    public function availableKeys(Form $form)
    {
        return response()->json([
            'keys' => AlsernetForm::query()
                ->where(fn ($q) => $q->whereNull('form_id')->orWhere('form_id', $form->id))
                ->orderBy('name')
                ->get(['form_key', 'name'])
                ->map(fn ($row) => ['key' => $row->form_key, 'label' => $row->name]),
        ]);
    }
}
