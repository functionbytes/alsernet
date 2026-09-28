<?php

namespace Modules\HelpdeskChatFlow\Services\Input;

use Modules\HelpdeskChatFlow\Events\ChatFlowCompleted;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowIdentityOtp;
use Modules\HelpdeskChatFlow\Services\CustomerIdentityResolver;

/**
 * Customer replies on an `identify_customer` node: lookup, OTP challenge and
 * verification, and the failure path. Never runs nodes itself — it returns the
 * node the engine must continue from (null = stay on the node or ended).
 */
class IdentificationInputHandler
{
    use RepliesOnNode;

    public function __construct(
        private readonly CustomerIdentityResolver $identityResolver,
        private readonly ChatFlowIdentityOtp $identityOtp,
    ) {}

    /**
     * Processes the customer's reply on an identify_customer node. Returns the
     * node to continue from, or null to stay on the node / end the session.
     *
     * @param  array<string, mixed>  $node
     */
    public function handle(ChatFlowSession $session, array $node, string $message): ?string
    {
        $data = $node['data'] ?? [];
        $sources = $data['sources'] ?? ['erp', 'ps'];
        $maxAttempts = (int) ($data['max_attempts'] ?? 3);

        // Verificación de identidad por OTP (por defecto activa): sin ella, un
        // cliente auto-identificado con el email de un tercero podía ver sus
        // pedidos (IDOR). Se puede desactivar por nodo con require_otp=false
        // para flujos que no exponen datos sensibles.
        $requireOtp = $data['require_otp'] ?? true;

        // Si ya enviamos un código, este mensaje es la respuesta con el código.
        if ($requireOtp && $this->identityOtp->isPending($session)) {
            return $this->processOtpReply($session, $node, $data, $message);
        }

        $customer = $this->identityResolver->resolve($message, $sources);

        if ($customer !== null) {
            // Con OTP: no marcar identificado todavía — enviar el código al
            // email registrado (fuera de banda) y esperar a que lo introduzca.
            if ($requireOtp) {
                if ($this->identityOtp->challenge($session, $customer)) {
                    $this->sendBotMessage($session, $node, $data['otp_sent_message']
                        ?? 'Por tu seguridad, te hemos enviado un código de verificación a tu email registrado. Introdúcelo aquí para continuar.');

                    return null; // sigue en este nodo esperando el código
                }

                // Sin email al que enviar el código: no se puede verificar, así
                // que NO exponemos sus datos (fail-closed).
                return $this->fail($session, $node, $data);
            }

            return $this->markIdentified($session, $node, $customer, $data, viaOtp: false);
        }

        $attempts = (int) ($session->getContextValue('_identify_attempts_'.$node['id']) ?? 0) + 1;
        $session->setContextValue('_identify_attempts_'.$node['id'], $attempts);

        if ($attempts >= $maxAttempts) {
            return $this->fail($session, $node, $data);
        }

        $notFoundMsg = $data['not_found_message'] ?? 'No encontramos ningún cliente con ese dato. Intenta con tu email, teléfono o número de documento de identidad.';
        $this->sendBotMessage($session, $node, $notFoundMsg);

        return null;
    }

    /**
     * Procesa el código OTP que el cliente introduce tras la identificación.
     *
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $data
     */
    private function processOtpReply(ChatFlowSession $session, array $node, array $data, string $message): ?string
    {
        $result = $this->identityOtp->verify($session, $message);

        if ($result === 'ok') {
            $customer = $this->identityOtp->pendingCustomer($session) ?? [];
            $this->identityOtp->clear($session);

            return $this->markIdentified($session, $node, $customer, $data, viaOtp: true);
        }

        // Código caducado o demasiados intentos: se cierra el intento de
        // verificación y se trata como identificación fallida.
        if (in_array($result, ['expired', 'locked'], true)) {
            $this->identityOtp->clear($session);

            return $this->fail($session, $node, $data);
        }

        // Código incorrecto: pedir de nuevo (los intentos los acota el propio
        // verificador, que devuelve 'locked' al agotarlos).
        $this->sendBotMessage($session, $node, $data['otp_invalid_message']
            ?? 'El código no es correcto. Revísalo e introdúcelo de nuevo.');

        return null;
    }

    /**
     * Marca al cliente como identificado en el contexto, emite el mensaje de
     * bienvenida y devuelve el siguiente nodo. Solo se llama tras verificar la
     * identidad (o con require_otp=false).
     *
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $data
     * @param  bool  $viaOtp  true solo cuando esta identificación pasó por la
     *                        verificación OTP real — customer_identified por
     *                        sí solo NO basta para exponer datos sensibles
     *                        (pedidos): un flow con require_otp=false deja
     *                        "identificado" a cualquiera que escriba el email
     *                        de un tercero, sin verificar que sea suyo de
     *                        verdad. customer_identified_via_otp es el flag
     *                        que ChatFlowAgentService::lookup_order exige.
     */
    private function markIdentified(ChatFlowSession $session, array $node, array $customer, array $data, bool $viaOtp): ?string
    {
        $values = ['customer_identified' => true, 'customer_identified_via_otp' => $viaOtp];
        foreach ($customer as $key => $value) {
            $values['customer_'.$key] = $value;
        }
        $values['customer_name'] = $customer['name'] ?? '';
        $values['customer_email'] = $customer['email'] ?? '';
        $session->setContextValues($values);

        if (! empty($data['found_message'])) {
            $text = preg_replace_callback('/\{\{(\w+)\}\}/', fn ($m) => $session->getContextValue($m[1], $m[0]), $data['found_message']);
            $this->sendBotMessage($session, $node, $text);
        }

        // Continue linearly — designer places branches node next if found/not_found split needed
        return $this->firstChildId($session, $node['id']);
    }

    /**
     * Identification failed: continue through the `else` branch of a following
     * branches node when there is one, otherwise hand off to a human.
     *
     * @param  array<string, mixed>  $node
     * @param  array<string, mixed>  $data
     */
    private function fail(ChatFlowSession $session, array $node, array $data): ?string
    {
        $session->setContextValue('customer_identified', false);
        $session->setContextValue('customer_identified_via_otp', false);

        // Look for an else branchItem sibling if the next node is a branches container
        $nextNodeId = $this->firstChildId($session, $node['id']);
        $nextNode = $nextNodeId ? $session->chatFlow->getNodeById($nextNodeId) : null;

        if ($nextNode && $nextNode['type'] === 'branches') {
            $elseBranchItem = collect($session->chatFlow->getBranchItems($nextNode['id']))
                ->first(fn ($b) => $b['data']['isElse'] ?? false);

            if ($elseBranchItem) {
                $afterElse = $this->firstChildId($session, $elseBranchItem['id']);
                if ($afterElse && $session->chatFlow->getNodeById($afterElse)) {
                    return $afterElse;
                }
            }
        }

        if ($data['transfer_on_failure'] ?? true) {
            $session->conversation?->releaseFromBot();
            $session->update(['status' => 'transferred', 'ended_at' => now()]);
            ChatFlowCompleted::dispatch($session);
        }

        return null;
    }
}
