<?php

namespace Modules\HelpdeskBirthday\Services\Redemption;

/**
 * De dónde se leen los canjes de bono de la tienda.
 *
 * Hay dos formas de preguntárselo a PrestaShop y las dos son legítimas: por el
 * bridge (HTTP firmado, la buena para producción, donde la tienda puede estar
 * en otra máquina) y por SQL directo (sirve mientras webadmin y PrestaShop
 * compartan MariaDB, que es el caso en desarrollo).
 *
 * La interfaz existe para que el resto del módulo no sepa cuál está usando: el
 * job de sincronización pide canjes y ya. Y para que quedarse sin bridge —caído,
 * circuito abierto, aún sin desplegar— no bloquee el trabajo.
 */
interface BirthdayRedemptionReader
{
    /**
     * ¿Se puede leer de esta fuente ahora mismo?
     */
    public function isAvailable(): bool;

    /**
     * Canjes de un rango de fechas.
     *
     * Se pide por fechas y nombre de cupón, NO por lista de códigos: 90 días
     * son más de 50.000 códigos, y además así aparecen los canjes que luego no
     * se logra atribuir a ningún destinatario —que son información, no ruido:
     * es gente que compró con un código que le reenviaron.
     *
     * @param  string|null  $nameLike  filtro sobre el nombre del cupón en la tienda
     * @return array<int, array<string, mixed>> filas normalizadas; ver el
     *                                          contrato en BridgeRedemptionReader
     */
    public function redemptions(?string $from, ?string $to, ?string $nameLike = null, int $limit = 500, int $offset = 0): array;

    /**
     * Cuántos canjes hay en ese rango, para saber si hay más páginas.
     */
    public function count(?string $from, ?string $to, ?string $nameLike = null): int;

    /** Nombre corto de la fuente, para los registros. */
    public function label(): string;
}
