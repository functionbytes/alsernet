<?php

namespace Modules\HelpdeskBirthday\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpdeskEmailActivity\Enums\SuppressionReason;
use Modules\HelpdeskEmailActivity\Services\EmailSuppressionService;

/**
 * Baja del correo de cumpleaños desde el enlace del propio email.
 *
 * La supresión se guarda acotada al módulo ('HelpdeskBirthday'): darse de baja
 * de la felicitación no debe cortar los correos de un ticket que el cliente
 * tenga abierto.
 */
class UnsubscribeController extends Controller
{
    public function __construct(
        private readonly EmailSuppressionService $suppressions,
    ) {}

    public function unsubscribe(Request $request): View
    {
        $email = mb_strtolower(trim((string) $request->query('email')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            abort(404);
        }

        $this->suppressions->suppress(
            email: $email,
            reason: SuppressionReason::Unsubscribed,
            module: 'HelpdeskBirthday',
            notes: 'Baja desde el enlace del correo de cumpleaños.',
            automatic: true,
        );

        return view('helpdeskbirthday::unsubscribed', ['email' => $email]);
    }
}
