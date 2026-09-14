<?php

namespace Modules\GiftMessage\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\GiftMessage\Models\GiftMessageConfig;

class GiftMessageController extends Controller
{
    public function index(): View
    {
        $this->authorize('view', GiftMessageConfig::class);

        // La pantalla arranca vacia a proposito: el listado de pedidos con
        // mensaje regalo solo se pinta tras una busqueda explicita (Paso 1),
        // via AJAX. Asi no se carga de golpe el historico de pedidos al
        // entrar, que ni es util ni es barato de consultar.
        return view('giftmessage::admin.index', [
            'pageTitle' => 'Mensaje regalo',
        ]);
    }
}
