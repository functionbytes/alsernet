<?php

namespace Modules\HelpdeskEmailActivity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HelpdeskEmailActivity\Http\Requests\AnonymizeRecipientRequest;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Services\RecipientAnonymizerService;

/**
 * Derecho de supresión del RGPD sobre el log de correo: anonimizar todos los
 * envíos de una dirección (ver RecipientAnonymizerService para qué se borra y
 * qué se conserva, y por qué).
 *
 * La acción es IRREVERSIBLE, así que va con tres cinturones: permiso de
 * gestión, confirmación escrita de la dirección (AnonymizeRecipientRequest) y
 * registro en la bitácora con quién la ejecutó.
 */
class EmailGdprController extends Controller
{
    public function __construct(private readonly RecipientAnonymizerService $anonymizer) {}

    /**
     * Alcance de la operación ANTES de ejecutarla: cuántos envíos, cuántos con
     * cuerpo y cuántos con adjuntos.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('manage', EmailLog::class);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        return response()->json([
            'success' => true,
            'preview' => $this->anonymizer->preview($validated['email']),
        ]);
    }

    public function anonymize(AnonymizeRecipientRequest $request): JsonResponse
    {
        $email = mb_strtolower(trim($request->validated('email')));

        $result = $this->anonymizer->anonymize(
            $email,
            $request->boolean('purge_body'),
            $request->boolean('replace_address'),
            $request->boolean('suppress'),
        );

        // La bitácora guarda el seudónimo, NO la dirección original: dejarla
        // escrita aquí convertiría el propio registro de auditoría en la copia
        // del dato que se acaba de borrar.
        activity('email-log')
            ->causedBy($request->user())
            ->withProperties([
                'pseudonym' => $this->anonymizer->pseudonymFor($email),
                'emails' => $result['emails'],
                'purge_body' => $request->boolean('purge_body'),
                'replace_address' => $request->boolean('replace_address'),
                'suppress' => $request->boolean('suppress'),
            ])
            ->event('gdpr_anonymized')
            ->log('gdpr_anonymized');

        return response()->json([
            'success' => true,
            'message' => trans_choice('helpdeskemailactivity::emaillog.gdpr.done', $result['emails'], ['count' => $result['emails']]),
        ]);
    }
}
