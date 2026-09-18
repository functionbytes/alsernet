<?php

namespace Modules\HelpdeskSocial\Http\Controllers\Settings;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Solo lectura: config('helpdesksocial.*') se resuelve una vez al boot desde
 * config/config.php + .env, no hay un almacén de settings persistente detrás.
 * Antes había un update() que aceptaba el formulario, validaba, y redirigía
 * con "Configuración actualizada" sin escribir nada en ningún sitio — el
 * cambio nunca se aplicaba pese al mensaje de éxito. Cambiar estos valores
 * de verdad requiere editar .env y reiniciar el servicio.
 */
class SocialModuleSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesksocial.view');
    }

    public function index(): View
    {
        return view('helpdesksocial::settings.index', [
            'config' => config('helpdesksocial'),
        ]);
    }
}
