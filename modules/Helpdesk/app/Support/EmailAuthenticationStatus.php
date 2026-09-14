<?php

namespace Modules\Helpdesk\Support;

/**
 * Resultado SPF/DKIM/DMARC de un correo entrante, parseado a partir de la
 * cabecera estándar `Authentication-Results` (RFC 8601) que el MX receptor
 * del proveedor (Mailgun/Postmark) añade, o de los campos dedicados que
 * SendGrid entrega directamente en su Inbound Parse webhook (sin cabecera
 * cruda) — ver EmailInboundController::parsePayload().
 *
 * FASE 1 (ver SEC-07 item 4): esto es solo para marcar/registrar. NO se usa
 * para rechazar ni poner en cuarentena el correo — un despliegue en dos
 * fases evita convertir un falso negativo de SPF/DKIM (muy común con
 * reenvíos, listas de correo, o proveedores mal configurados) en pérdida de
 * correo legítimo. La única decisión activa que SÍ se toma con esto es de
 * autorización interna: si el correo puede continuar un hilo existente de
 * OTRO cliente (ver EmailInboundService::resolveConversation()).
 */
final class EmailAuthenticationStatus
{
    public function __construct(
        public readonly ?string $spf = null,
        public readonly ?string $dkim = null,
        public readonly ?string $dmarc = null,
        public readonly ?string $alignedDomain = null,
    ) {}

    public static function fromAuthenticationResultsHeader(?string $header, ?string $spfHint = null, ?string $dkimHint = null): self
    {
        if (! $header) {
            return new self(spf: $spfHint, dkim: $dkimHint);
        }

        $aligned = self::extractDomain($header, 'header.d') ?? self::extractDomain($header, 'header.from');

        return new self(
            spf: self::extractResult($header, 'spf') ?? $spfHint,
            dkim: self::extractResult($header, 'dkim') ?? $dkimHint,
            dmarc: self::extractResult($header, 'dmarc'),
            alignedDomain: $aligned,
        );
    }

    private static function extractResult(string $header, string $mechanism): ?string
    {
        return preg_match('/\b'.preg_quote($mechanism, '/').'=([a-z]+)/i', $header, $m)
            ? mb_strtolower($m[1])
            : null;
    }

    private static function extractDomain(string $header, string $key): ?string
    {
        return preg_match('/\b'.preg_quote($key, '/').'=([a-z0-9.-]+)/i', $header, $m)
            ? mb_strtolower($m[1])
            : null;
    }

    /**
     * DMARC "pass" ya implica alineación de SPF o DKIM con el dominio
     * visible del From (esa es la definición de DMARC); si no vino
     * evaluación DMARC, exige SPF y DKIM en "pass" a la vez.
     */
    public function passed(): bool
    {
        return $this->dmarc === 'pass' || ($this->spf === 'pass' && $this->dkim === 'pass');
    }

    /**
     * $fromDomain sin alineación de dominio verificada (aligned_domain
     * vacío porque el proveedor no lo entregó, p.ej. SendGrid solo da
     * spf/dkim sueltos) nunca se considera alineado, aunque haya pasado —
     * más estricto a propósito.
     */
    public function isAlignedWith(string $fromDomain): bool
    {
        if (! $this->passed() || $this->alignedDomain === null || $fromDomain === '') {
            return false;
        }

        $fromDomain = mb_strtolower(ltrim($fromDomain, '@'));

        return $this->alignedDomain === $fromDomain || str_ends_with($fromDomain, '.'.$this->alignedDomain);
    }

    /**
     * @return array{spf: ?string, dkim: ?string, dmarc: ?string, aligned_domain: ?string, passed: bool}
     */
    public function toArray(): array
    {
        return [
            'spf' => $this->spf,
            'dkim' => $this->dkim,
            'dmarc' => $this->dmarc,
            'aligned_domain' => $this->alignedDomain,
            'passed' => $this->passed(),
        ];
    }
}
