<?php

namespace Modules\Erp\Services;

class ErpCustomerDataService
{
    public function __construct(
        private readonly OCI8Service $oci8,
    ) {}

    /**
     * Busca un cliente por email. Devuelve null si no existe.
     *
     * @return array{idcliente: int, nombre: string, apellidos: string, cif: string|null, email: string}|null
     */
    public function findByEmail(string $email): ?array
    {
        $rows = $this->oci8->query(
            'SELECT IDCLIENTE, NOMBRE, APELLIDOS, CIF, EMAIL FROM DEVELOPER.CLIENTE_CENT '.
            'WHERE FBAJA IS NULL AND UPPER(EMAIL) = :email AND ROWNUM <= 1',
            ['email' => strtoupper($email)],
            10000
        );

        return $rows[0] ?? null;
    }

    /**
     * Busca un cliente por número de teléfono (dígitos puros).
     *
     * @return array{idcliente: int, nombre: string, apellidos: string, cif: string|null, email: string|null}|null
     */
    public function findByPhone(string $phoneDigits): ?array
    {
        $rows = $this->oci8->query(
            'SELECT c.IDCLIENTE, c.NOMBRE, c.APELLIDOS, c.CIF, c.EMAIL '.
            'FROM DEVELOPER.CLIENTE_CENT c '.
            'WHERE c.FBAJA IS NULL AND c.IDCLIENTE IN ('.
            'SELECT IDCLIENTE FROM DEVELOPER.CLIENTETELEFONO_CENT WHERE TELEFONO = :phone AND ROWNUM <= 1'.
            ') AND ROWNUM <= 1',
            ['phone' => $phoneDigits],
            10000
        );

        return $rows[0] ?? null;
    }

    /**
     * Devuelve los N pedidos más recientes de un cliente.
     *
     * @return list<array{idpedidocli_central: int, npedidocli: string, estado: int, fpedido: string, fentrega: string|null, fservido: string|null, idalmacen: string|null, idtipopedido: string|null, observaciones: string|null}>
     */
    public function getRecentOrders(int $idcliente, int $limit = 10): array
    {
        $n = max(1, (int) $limit);

        return $this->oci8->query(
            'SELECT * FROM ('.
            'SELECT IDPEDIDOCLI_CENTRAL, NPEDIDOCLI, ESTADO, '.
            "TO_CHAR(FPEDIDO, 'YYYY-MM-DD HH24:MI:SS') AS FPEDIDO, ".
            "TO_CHAR(FPREVISTA, 'YYYY-MM-DD') AS FPREVISTA, ".
            "TO_CHAR(FSERVIDO, 'YYYY-MM-DD HH24:MI:SS') AS FSERVIDO, ".
            'IDALMACEN, TIPOPEDIDO, OBSERVACIONES '.
            'FROM DEVELOPER.PEDIDOCLI_CENTRAL '.
            'WHERE IDCLIENTE = :id ORDER BY FPEDIDO DESC'.
            ") WHERE ROWNUM <= {$n}",
            ['id' => $idcliente],
            20000
        );
    }

    /**
     * Devuelve el detalle completo de un pedido: cabecera, líneas, forma de pago y dirección.
     * Retorna null si el pedido no existe o el usuario DB no tiene acceso.
     *
     * @return array{id: int, number: string|null, status: int|null, date: string|null, phone: string|null,
     *               payment_method: string|null, address: string|null,
     *               lines: list<array{name: string, qty: int, price: float, discount: float, vat: float}>,
     *               total: float|null}|null
     */
    public function getOrderDetail(int $orderId, int $idcliente): ?array
    {
        // Cabecera del pedido
        $rows = $this->oci8->query(
            'SELECT IDPEDIDOCLI_CENTRAL, NPEDIDOCLI, ESTADO, CLIENTETELEFONO, '.
            "TO_CHAR(FPEDIDO, 'YYYY-MM-DD') AS FPEDIDO, ".
            "TO_CHAR(FPREVISTA, 'YYYY-MM-DD') AS FPREVISTA, ".
            "TO_CHAR(FSERVIDO, 'YYYY-MM-DD') AS FSERVIDO, ".
            'IDALMACEN, TIPOPEDIDO, OBSERVACIONES '.
            'FROM DEVELOPER.PEDIDOCLI_CENTRAL '.
            'WHERE IDPEDIDOCLI_CENTRAL = :id AND ROWNUM <= 1',
            ['id' => $orderId],
            10000
        );

        if (empty($rows)) {
            return null;
        }

        $order = $rows[0];

        // Líneas del pedido con descripción del artículo
        $lines = [];
        try {
            $lineRows = $this->oci8->query(
                'SELECT L.UNIDADES, L.PRECIO, L.DTO, L.IVA, A.DESCRIPCION AS DESCRIPCION '.
                'FROM DEVELOPER.LPEDIDOCLI_CENTRAL L '.
                'LEFT JOIN DEVELOPER.ARTICULO A ON A.IDARTICULO = L.IDARTICULO '.
                'WHERE L.IDPEDIDOCLI_CENTRAL = :id',
                ['id' => $orderId],
                30000
            );
            foreach ($lineRows as $l) {
                $lines[] = [
                    'name' => $l['descripcion'] ?? 'Artículo',
                    'qty' => (int) ($l['unidades'] ?? 1),
                    'price' => (float) ($l['precio'] ?? 0),
                    'discount' => (float) ($l['dto'] ?? 0),
                    'vat' => (float) ($l['iva'] ?? 0),
                ];
            }
        } catch (\Throwable) {
            // LECTURA puede no tener acceso a LPEDIDOCLI_CENTRAL o ARTICULO
        }

        // Forma de pago
        $paymentMethod = null;
        try {
            $fps = $this->oci8->query(
                'SELECT F.DESCRIPCION AS DESCRIPCION '.
                'FROM DEVELOPER.FPPEDCLI_CENTRAL FP '.
                'LEFT JOIN DEVELOPER.FORMAPAGO F ON F.IDFORMAPAGO = FP.IDFORMAPAGO '.
                'WHERE FP.IDPEDIDOCLI_CENTRAL = :id AND FP.FBAJA IS NULL AND ROWNUM <= 1',
                ['id' => $orderId],
                10000
            );
            $paymentMethod = $fps[0]['descripcion'] ?? null;
        } catch (\Throwable) {
        }

        // Dirección de envío del cliente (tipo 1)
        $address = null;
        try {
            $addrs = $this->oci8->query(
                'SELECT CALLE, NUM, CODIGOPOSTAL, POBLACION, PROVINCIA '.
                'FROM DEVELOPER.CLIENTEDIRECCION_CENT '.
                'WHERE IDCLIENTE = :cid AND FBAJA IS NULL AND IDTIPODIRECCION = 1 AND ROWNUM <= 1',
                ['cid' => $idcliente],
                10000
            );
            if (! empty($addrs)) {
                $a = $addrs[0];
                $parts = array_filter([
                    trim(($a['calle'] ?? '').' '.($a['num'] ?? '')),
                    $a['codigopostal'] ?? null,
                    $a['poblacion'] ?? null,
                    $a['provincia'] ?? null,
                ]);
                $address = implode(', ', $parts) ?: null;
            }
        } catch (\Throwable) {
        }

        // Total calculado desde líneas
        $total = null;
        if (! empty($lines)) {
            $total = array_reduce($lines, function (float $carry, array $l): float {
                $net = $l['qty'] * $l['price'] * (1 - $l['discount'] / 100);

                return $carry + $net * (1 + $l['vat'] / 100);
            }, 0.0);
            $total = round($total, 2);
        }

        return [
            'id' => (int) ($order['idpedidocli_central'] ?? $orderId),
            'number' => $order['npedidocli'] ?? null,
            'status' => isset($order['estado']) ? (int) $order['estado'] : null,
            'date' => $order['fpedido'] ?? null,
            'expected_date' => $order['fprevista'] ?? null,
            'served_date' => $order['fservido'] ?? null,
            'warehouse' => $order['idalmacen'] ?? null,
            'observations' => $order['observaciones'] ?? null,
            'phone' => $order['clientetelefono'] ?? null,
            'payment_method' => $paymentMethod,
            'address' => $address,
            'lines' => $lines,
            'total' => $total,
        ];
    }

    /**
     * Devuelve las N facturas más recientes de un cliente.
     * Retorna [] si el usuario DB no tiene acceso a FACTURACLI_CENTRAL.
     *
     * @return list<array{nfactura: string, serie: string|null, anno: string|null, ffactura: string, estado: int, formadepago: string|null}>
     */
    public function getRecentInvoices(int $idcliente, int $limit = 5): array
    {
        $n = max(1, (int) $limit);

        try {
            return $this->oci8->query(
                'SELECT * FROM ('.
                'SELECT NFACTURA, SERIE, ANNO, '.
                "TO_CHAR(FFACTURA, 'YYYY-MM-DD') AS FFACTURA, ".
                'ESTADO, FORMADEPAGO '.
                'FROM DEVELOPER.FACTURACLI_CENTRAL '.
                'WHERE IDCLIENTE = :id ORDER BY FFACTURA DESC'.
                ") WHERE ROWNUM <= {$n}",
                ['id' => $idcliente],
                10000
            );
        } catch (\Throwable) {
            // El usuario OCI8 (LECTURA) puede no tener SELECT sobre FACTURACLI_CENTRAL
            return [];
        }
    }
}
