<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\AccountActionRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\AccountErasureRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\AccountGroupRequest;
use Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext\AccountUpdateRequest;
use Modules\HelpdeskPrestashop\Services\Ext\AccountService;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cuenta del cliente en PrestaShop desde el workspace del chat: ficha
 * editable (customer.update del diseño), grupo y descuento (28), acceso a la
 * cuenta (29) y RGPD (30).
 *
 * Cada escritura exige: permiso propio helpdeskprestashop.account.*, acceso
 * de edición a ESTE cliente (CustomerPolicy::update, aislamiento por inbox),
 * clave de idempotencia y entrada en el log de actividad con el agente y la
 * conversación. La propiedad en la tienda la resuelve el servicio desde el
 * Customer, nunca desde el body.
 */
class AccountController extends Controller
{
    private const ABILITIES = [
        'update' => 'helpdeskprestashop.account.update',
        'group' => 'helpdeskprestashop.account.group',
        'password_reset' => 'helpdeskprestashop.account.password_reset',
        'gdpr_export' => 'helpdeskprestashop.account.gdpr_export',
        'gdpr_request' => 'helpdeskprestashop.account.gdpr_request',
    ];

    public function __construct(
        private readonly AccountService $service
    ) {}

    public function show(Request $request, Customer $customer): JsonResponse
    {
        $user = $request->user();

        if (! $user?->can('helpdeskprestashop.view') || ! $user->can('view', $customer)) {
            return response()->json(['success' => false], 403);
        }

        try {
            $profile = $this->service->profile($customer);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde ahora mismo.'], 503);
        }

        if ($profile === null) {
            return response()->json(['success' => false, 'message' => 'Este contacto no tiene cuenta en PrestaShop.'], 404);
        }

        return response()->json(['success' => true, 'data' => $profile, 'can' => $this->abilities($user, $customer)]);
    }

    public function update(AccountUpdateRequest $request, Customer $customer): JsonResponse
    {
        if (($deny = $this->deny($request, $customer, 'update')) !== null) {
            return $deny;
        }

        $changes = $request->changes();

        // Foto de antes para la auditoría (qué valía cada campo). Si el
        // puente no responde a esta lectura tampoco podría guardar.
        try {
            $before = $this->service->profile($customer);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde ahora mismo.'], 503);
        }

        try {
            $result = $this->service->update($customer, $changes, $this->idempotencyKey($request, $customer, 'account.update', $changes));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido guardar la ficha ahora mismo.'], 503);
        }

        if (! is_array($result)) {
            return response()->json(['success' => false, 'message' => 'PrestaShop ha rechazado el cambio.'], 422);
        }

        if (($result['ok_semantic'] ?? true) === false) {
            // Escritura parcial: la ficha se guardó y el teléfono no. Lo que sí
            // cambió en la tienda también se audita.
            if (! empty($result['changed'])) {
                $this->audit($request, $customer, 'ps.account.updated', [
                    'changed' => $result['changed'],
                    'before' => $this->pick($before, $result['changed']),
                    'after' => collect($changes)->only($result['changed'])->all(),
                    'partial' => true,
                    'error' => $result['error'] ?? null,
                ]);
            }

            return $this->semanticError($result);
        }

        if (! empty($result['updated'])) {
            $this->audit($request, $customer, 'ps.account.updated', [
                'changed' => $result['changed'] ?? [],
                'before' => $this->pick($before, $result['changed'] ?? []),
                'after' => collect($changes)->only($result['changed'] ?? [])->all(),
                'address_id' => $result['address_id'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function group(AccountGroupRequest $request, Customer $customer): JsonResponse
    {
        if (($deny = $this->deny($request, $customer, 'group')) !== null) {
            return $deny;
        }

        $data = $request->validated();

        try {
            $result = $this->service->setGroup(
                $customer,
                (int) $data['group_id'],
                $data['version'],
                $this->idempotencyKey($request, $customer, 'account.set_group', ['g' => (int) $data['group_id'], 'v' => $data['version']]),
            );
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido cambiar el grupo ahora mismo.'], 503);
        }

        if (! is_array($result)) {
            return response()->json(['success' => false, 'message' => 'PrestaShop ha rechazado el cambio de grupo.'], 422);
        }

        if (($result['ok_semantic'] ?? true) === false) {
            return $this->semanticError($result);
        }

        if (! empty($result['updated'])) {
            $this->audit($request, $customer, 'ps.account.group_changed', [
                'from' => $result['previous'] ?? null,
                'to' => $result['group'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function passwordReset(AccountActionRequest $request, Customer $customer): JsonResponse
    {
        if (($deny = $this->deny($request, $customer, 'password_reset')) !== null) {
            return $deny;
        }

        // Un envío por agente, cliente y minuto: el doble clic no manda dos correos.
        try {
            $result = $this->service->sendPasswordReset($customer, $this->idempotencyKey($request, $customer, 'account.password_reset', []));
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no ha podido enviar el correo ahora mismo.'], 503);
        }

        if (! is_array($result)) {
            return response()->json(['success' => false, 'message' => 'PrestaShop ha rechazado el envío.'], 422);
        }

        if (($result['ok_semantic'] ?? true) === false) {
            return $this->semanticError($result);
        }

        $this->audit($request, $customer, 'ps.account.password_reset_sent', [
            'to' => $result['to'] ?? null,
            'valid_until' => $result['valid_until'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Exportación RGPD: descarga JSON con los datos personales que el puente
     * puede leer. Se audita siempre, incluso si la descarga no llega a abrirse.
     */
    public function gdprExport(Request $request, Customer $customer): Response
    {
        if (($deny = $this->deny($request, $customer, 'gdpr_export', 'view')) !== null) {
            return $deny;
        }

        try {
            $data = $this->service->gdprExport($customer);
        } catch (PsUpstreamException) {
            return response()->json(['success' => false, 'message' => 'PrestaShop no responde ahora mismo.'], 503);
        }

        if ($data === null) {
            return response()->json(['success' => false, 'message' => 'Este contacto no tiene cuenta en PrestaShop.'], 404);
        }

        $this->audit($request, $customer, 'ps.account.gdpr_exported', [
            'ps_customer_id' => $data['customer']['id'] ?? null,
            'truncated' => $data['truncated'] ?? null,
        ]);

        $user = $request->user();
        $payload = [
            'export' => [
                'type' => 'RGPD · derecho de acceso',
                'requested_by' => $user->name ?? $user->email ?? null,
                'helpdesk_customer_id' => $customer->id,
            ],
        ] + $data;

        $filename = sprintf('rgpd-cliente-PS%s-%s.json', $data['customer']['id'] ?? $customer->id, now()->format('Ymd-His'));

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * "Solicitar borrado de la cuenta": NO borra nada en la tienda. Deja la
     * solicitud en el log de actividad (quién, cuándo, desde qué conversación)
     * y devuelve el texto de la nota interna que el panel publica en la
     * conversación para que la confirme un responsable.
     */
    public function erasureRequest(AccountErasureRequest $request, Customer $customer): JsonResponse
    {
        if (($deny = $this->deny($request, $customer, 'gdpr_request')) !== null) {
            return $deny;
        }

        $data = $request->validated();
        $user = $request->user();
        $psId = $this->service->externalId($customer);
        $reason = trim((string) ($data['reason'] ?? ''));

        $pending = $this->pendingErasureRequest($customer);
        if ($pending !== null) {
            return response()->json([
                'success' => true,
                'already_requested' => true,
                'requested_at' => $pending->created_at?->toIso8601String(),
                'message' => 'Ya hay una solicitud de borrado pendiente de confirmar para este cliente.',
            ]);
        }

        $this->audit($request, $customer, 'ps.account.erasure_requested', [
            'ps_customer_id' => $psId,
            'reason' => $reason !== '' ? $reason : null,
            'status' => 'pending_confirmation',
        ]);

        $note = '[PS · RGPD] Solicitud de borrado de la cuenta'
            .($psId ? ' PS-'.$psId : '')
            .' ('.($customer->email ?: 'sin email').'), registrada por '.($user->name ?? $user->email ?? 'un agente')
            .'. No se ha borrado nada: debe confirmarla un responsable.'
            .($reason !== '' ? ' Motivo: '.$reason : '');

        return response()->json(['success' => true, 'already_requested' => false, 'note' => $note]);
    }

    /**
     * @return array<string, bool>
     */
    private function abilities(User $user, Customer $customer): array
    {
        $canEditCustomer = $user->can('update', $customer);
        $out = [];
        foreach (self::ABILITIES as $key => $permission) {
            $out[$key] = $user->can($permission) && ($key === 'gdpr_export' ? true : $canEditCustomer);
        }

        return $out;
    }

    private function deny(Request $request, Customer $customer, string $ability, string $customerAbility = 'update'): ?JsonResponse
    {
        $user = $request->user();

        if (! $user?->can(self::ABILITIES[$ability]) || ! $user->can($customerAbility, $customer)) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para esta acción.'], 403);
        }

        if (trim((string) $customer->email) === '' && $this->service->externalId($customer) === null) {
            return response()->json(['success' => false, 'message' => 'El cliente no está vinculado a PrestaShop.'], 422);
        }

        return null;
    }

    private function semanticError(array $result): JsonResponse
    {
        $error = (string) ($result['error'] ?? '');

        if ($error === 'version_conflict') {
            return response()->json([
                'success' => false,
                'conflict' => true,
                'message' => 'La ficha ha cambiado en PrestaShop desde que la abriste. Recárgala antes de guardar.',
            ], 409);
        }

        $messages = [
            'not_found' => 'El cliente ya no existe en PrestaShop.',
            'invalid_firstname' => 'PrestaShop no acepta ese nombre.',
            'invalid_lastname' => 'PrestaShop no acepta esos apellidos.',
            'invalid_language' => 'Ese idioma no está activo en la tienda.',
            'invalid_phone' => 'PrestaShop no acepta ese teléfono.',
            'address_not_owned' => 'La dirección del teléfono ya no es del cliente o se ha borrado. Recarga la ficha.',
            'phone_save_failed' => 'Se guardó la ficha, pero no el teléfono.',
            'save_failed' => 'PrestaShop no ha aceptado los datos (puede que la ficha tenga un dato antiguo no válido).',
            'invalid_group' => 'Ese grupo no se puede asignar a un cliente registrado.',
            'guest_account' => 'Es una cuenta de invitado: no tiene contraseña que restablecer.',
            'inactive_account' => 'La cuenta está desactivada: la tienda no permite restablecer su contraseña.',
            'too_soon' => 'La contraseña se cambió hace muy poco; la tienda no permite otro enlace todavía.',
            'mail_failed' => 'La tienda no ha podido enviar el correo.',
        ];

        return response()->json([
            'success' => false,
            'error' => $error,
            'saved' => $result['changed'] ?? [],
            'next_reset_at' => $result['next_reset_at'] ?? null,
            'message' => $messages[$error] ?? 'PrestaShop ha rechazado el cambio.',
        ], 422);
    }

    private function audit(Request $request, Customer $customer, string $event, array $properties): void
    {
        if (! function_exists('activity')) {
            return;
        }

        activity('helpdeskprestashop')
            ->causedBy($request->user())
            ->performedOn($customer)
            ->withProperties($properties + ['conversation_id' => $request->integer('conversation_id') ?: null])
            ->log($event);
    }

    private function pendingErasureRequest(Customer $customer): ?Activity
    {
        try {
            return Activity::query()
                ->where('log_name', 'helpdeskprestashop')
                ->where('description', 'ps.account.erasure_requested')
                ->where('subject_type', $customer->getMorphClass())
                ->where('subject_id', $customer->getKey())
                ->where('created_at', '>=', now()->subHours((int) config('helpdeskprestashop.ext.account.erasure_request_window_hours', 72)))
                ->latest()
                ->first();
        } catch (\Throwable $e) {
            Log::warning('HelpdeskPrestashop account: no se pudo consultar solicitudes de borrado previas.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, mixed>
     */
    private function pick(?array $profile, array $fields): array
    {
        if ($profile === null) {
            return [];
        }

        $out = [];
        foreach ($fields as $field) {
            $out[$field] = $field === 'phone' ? ($profile['phone']['value'] ?? null) : ($profile[$field] ?? null);
        }

        return $out;
    }

    /**
     * Clave determinista agente + cliente + acción + payload + minuto: el
     * doble clic o el reintento de red no repiten la escritura, pero el mismo
     * cambio hecho a propósito más tarde sí se ejecuta.
     */
    private function idempotencyKey(Request $request, Customer $customer, string $action, array $payload): string
    {
        $header = trim((string) $request->header('Idempotency-Key', ''));
        if ($header !== '') {
            return $header;
        }

        return sha1(implode(':', [
            (string) $request->user()?->getAuthIdentifier(),
            $customer->id,
            $action,
            json_encode($payload),
            now()->format('YmdHi'),
        ]));
    }
}
