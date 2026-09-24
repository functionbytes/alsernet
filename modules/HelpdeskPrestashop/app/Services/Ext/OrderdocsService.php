<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Lecturas de la extensión "orderdocs" contra el bridge: notas internas del
 * pedido, documentos y el PDF de cada documento.
 *
 * Todas van con lookup de propiedad (email y/o external_id): el bridge solo
 * devuelve el pedido si es de ese cliente, y el documento solo si es de ese
 * pedido. Sin lookup no se llama (fail-closed, mismo criterio que
 * PrestashopContextService::buildOwnershipLookup).
 */
class OrderdocsService
{
    public const TYPES = ['invoice', 'delivery_slip', 'credit_slip'];

    public function __construct(
        private readonly PrestashopContextService $bridge
    ) {}

    /**
     * @return array{order_id:int, notes:array<int, array{id:string,source:string,author:string,date:?string,text:string}>}|null
     *
     * @throws PsUpstreamException
     */
    public function notes(int $orderId, ?string $email, ?int $externalId): ?array
    {
        $lookup = $this->bridge->ownershipLookup($email, $externalId, 'orderdocs.notes');
        if ($lookup === null) {
            return null;
        }

        return $this->bridge->callBridge('orderdocs.notes', [
            'order_id' => $orderId,
            'lookup' => $lookup,
        ]);
    }

    /**
     * @return array{order_id:int, reference:string, invoicing_enabled:bool, invoices:array, delivery_slips:array, credit_slips:array}|null
     *
     * @throws PsUpstreamException
     */
    public function documents(int $orderId, ?string $email, ?int $externalId): ?array
    {
        $lookup = $this->bridge->ownershipLookup($email, $externalId, 'orderdocs.list');
        if ($lookup === null) {
            return null;
        }

        return $this->bridge->callBridge('orderdocs.list', [
            'order_id' => $orderId,
            'lookup' => $lookup,
        ]);
    }

    /**
     * PDF ya decodificado. null si el bridge no lo encuentra, no es del
     * cliente, no se pudo generar o lo que llega no es un PDF.
     *
     * @return array{filename:string, binary:string}|null
     *
     * @throws PsUpstreamException
     */
    public function pdf(int $orderId, string $type, int $docId, ?string $email, ?int $externalId): ?array
    {
        if (! in_array($type, self::TYPES, true)) {
            return null;
        }

        $lookup = $this->bridge->ownershipLookup($email, $externalId, 'orderdocs.pdf');
        if ($lookup === null) {
            return null;
        }

        $data = $this->bridge->callBridge('orderdocs.pdf', [
            'order_id' => $orderId,
            'type' => $type,
            'doc_id' => $docId,
            'lookup' => $lookup,
        ]);

        if (! is_array($data) || ! isset($data['content_base64']) || ($data['ok_semantic'] ?? true) === false) {
            return null;
        }

        $binary = base64_decode((string) $data['content_base64'], true);
        $max = (int) config('helpdeskprestashop.ext.orderdocs.pdf_max_bytes', 8 * 1024 * 1024);

        if ($binary === false || $binary === '' || strlen($binary) > $max || ! str_starts_with($binary, '%PDF')) {
            return null;
        }

        return [
            'filename' => $this->safeFilename((string) ($data['filename'] ?? ''), $type, $orderId),
            'binary' => $binary,
        ];
    }

    /**
     * El nombre viene del bridge (DE050805.pdf, 000063.pdf…): se limpia para
     * que nunca pueda romper la cabecera Content-Disposition, y se le antepone
     * el tipo, porque "000063.pdf" no le dice nada al cliente que lo recibe.
     */
    private function safeFilename(string $name, string $type, int $orderId): string
    {
        $prefix = ['invoice' => 'factura', 'delivery_slip' => 'albaran', 'credit_slip' => 'abono'][$type];
        $name = preg_replace('/[^A-Za-z0-9._-]/', '', $name) ?? '';

        if ($name === '' || ! str_ends_with(strtolower($name), '.pdf')) {
            return $prefix.'-'.$orderId.'.pdf';
        }

        return $prefix.'-'.$name;
    }
}
