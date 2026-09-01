<?php

namespace Modules\HelpdeskEmailLog\Contracts;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\HelpdeskEmailLog\Support\ParsedEmailEvent;

/**
 * Un adapter solo sabe DOS cosas de su proveedor: verificar que el webhook es
 * legítimo, y traducir su payload propio a una lista de ParsedEmailEvent.
 * Nunca toca EmailLog directamente — eso lo hace siempre
 * EmailBounceCorrelatorService, desde el controlador genérico.
 */
interface EmailProviderWebhookAdapter
{
    /**
     * Clave corta usada en la URL (/webhooks/{provider}) y en el selector de
     * Settings — 'mailrelay'|'ses'|'postmark'|'mailgun'.
     */
    public function key(): string;

    /**
     * Verifica la firma/token del proveedor contra el secreto configurado.
     * false = el controlador responde 401 sin procesar nada.
     */
    public function verify(Request $request, string $secret): bool;

    /**
     * Manejo de mensajes de control del proveedor que NO son un evento de
     * entrega (p. ej. SNS SubscriptionConfirmation) — devuelve una Response
     * ya resuelta si el request se maneja aquí (el controlador la devuelve
     * tal cual y no sigue a parse()), o null si es un evento normal.
     */
    public function handleControlMessage(Request $request): ?Response;

    /**
     * @return list<ParsedEmailEvent>
     */
    public function parse(Request $request): array;
}
