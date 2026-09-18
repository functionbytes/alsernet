<?php

namespace Modules\HelpdeskIntegration\Support;

/**
 * Resultado tipado de una operación de driver contra la plataforma remota.
 * Distingue "la plataforma respondió sin coincidencias" (ok + results vacío)
 * de "la plataforma falló o no respondió" (failed) — con el array plano
 * anterior ambos casos llegaban como [] y el agente veía "Sin resultados"
 * aunque el ERP estuviera caído.
 */
final class DriverResult
{
    /**
     * @param  list<array{id: string, name: string, email: string, meta: string}>  $results
     */
    private function __construct(
        public readonly bool $ok,
        public readonly array $results = [],
    ) {}

    /**
     * @param  list<array{id: string, name: string, email: string, meta: string}>  $results
     */
    public static function ok(array $results): self
    {
        return new self(true, $results);
    }

    public static function failed(): self
    {
        return new self(false);
    }

    /**
     * Si la plataforma respondió y algún resultado coincide con el id dado.
     */
    public function found(string $externalId): bool
    {
        return $this->ok && collect($this->results)
            ->contains(fn (array $result) => (string) $result['id'] === $externalId);
    }
}
