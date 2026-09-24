{{-- Copia informativa de una factura de Gestión (DomPDF).
     $doc: ErpInvoiceDocument::fromDetail(). NO es la factura fiscal: la
     emite Gestión. Marca de agua + pie en todas las páginas.
     DomPDF: estilos en <style> (no hay CSS externo) y DejaVu Sans para
     acentos, eñes y €. --}}
@use('Modules\HelpdeskErp\Services\ErpInvoice\ErpInvoicePdfRenderer', 'R')
@php
    $company = $doc['company'];
    $customer = $doc['customer'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Copia de factura {{ $doc['ref'] }}</title>
    <style>
        @page { margin: 22mm 16mm 24mm 16mm; }
        * { font-family: "DejaVu Sans", sans-serif; }
        body { font-size: 9pt; color: #2b2b2b; margin: 0; }

        /* Fijos (se repiten en cada página). Sin floats ni anchos > 100 %:
           en DomPDF un fijo que desborda hace que pagine sin fin. */
        .wm {
            position: fixed; top: 105mm; left: 0; right: 0; height: 20mm;
            text-align: center; font-size: 26pt; font-weight: bold;
            color: #90bb13; opacity: .14;
            transform: rotate(-30deg);
        }
        .foot {
            position: fixed; bottom: -14mm; left: 0; right: 0; height: 10mm;
            border-top: 1px solid #d9e6b3; padding-top: 4px;
            font-size: 7pt; color: #565656;
        }
        .foot table { width: 100%; border-collapse: collapse; }
        .foot td { padding: 0; }
        .foot td.r { text-align: right; }

        .head { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .head td { vertical-align: top; padding: 0; }
        .brand { font-size: 13pt; font-weight: bold; color: #2d3324; }
        .muted { color: #6b6f66; }
        .small { font-size: 7.5pt; }
        .doc-box { text-align: right; }
        .doc-kind { font-size: 8pt; font-weight: bold; color: #5e7a0d; letter-spacing: 1px; text-transform: uppercase; }
        .doc-ref { font-size: 15pt; font-weight: bold; color: #2d3324; }
        .copy-tag {
            display: inline-block; margin-top: 4px; padding: 2px 7px;
            border: 1px solid #b6d34a; border-radius: 3px;
            font-size: 7pt; font-weight: bold; color: #5e7a0d; background: #f4f9e6;
        }
        .void-tag {
            display: inline-block; margin-top: 4px; padding: 2px 7px;
            border: 1px solid #565656; border-radius: 3px;
            font-size: 7pt; font-weight: bold; color: #ffffff; background: #565656;
        }

        .parties { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 12px; }
        .parties td { width: 50%; vertical-align: top; padding: 9px 10px; border: 1px solid #e3e8d6; background: #fafcf5; }
        .parties td.gap { width: 2%; border: 0; background: none; padding: 0; }
        .lbl { font-size: 6.8pt; font-weight: bold; color: #5e7a0d; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 3px; }
        .party-name { font-weight: bold; font-size: 9.5pt; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { padding: 5px 8px; border: 1px solid #e3e8d6; }
        .meta .k { font-size: 6.8pt; color: #6b6f66; text-transform: uppercase; letter-spacing: .4px; }
        .meta .v { font-weight: bold; }

        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.lines th {
            background: #2d3324; color: #ffffff; font-size: 7pt; font-weight: bold;
            text-transform: uppercase; letter-spacing: .4px; padding: 5px 6px; text-align: left;
        }
        table.lines th.num { text-align: right; }
        table.lines td { padding: 5px 6px; border-bottom: 1px solid #e8ece0; vertical-align: top; }
        table.lines tr:nth-child(even) td { background: #fafcf5; }
        .num { text-align: right; white-space: nowrap; }
        .code { font-size: 7.5pt; color: #6b6f66; }

        .sums { width: 100%; border-collapse: collapse; }
        .sums td { vertical-align: top; }
        table.taxes { width: 100%; border-collapse: collapse; }
        table.taxes th { font-size: 6.8pt; color: #5e7a0d; text-transform: uppercase; padding: 4px 6px; border-bottom: 1px solid #b6d34a; text-align: right; }
        table.taxes th:first-child { text-align: left; }
        table.taxes td { padding: 4px 6px; border-bottom: 1px solid #eef1e7; }
        table.total { width: 100%; border-collapse: collapse; }
        table.total td { padding: 4px 8px; }
        table.total .grand td { background: #90bb13; color: #ffffff; font-weight: bold; font-size: 11pt; padding: 7px 8px; }

        .obs { margin-top: 12px; padding: 8px 10px; border-left: 3px solid #b6d34a; background: #fafcf5; }
        .obs-v { white-space: pre-line; }
        .notice { margin-top: 14px; font-size: 7.5pt; color: #565656; }
    </style>
</head>
<body>
    <div class="wm">{{ $watermark }}</div>

    <div class="foot">
        <table>
            <tr>
                <td>{{ $watermark }}. La factura fiscal la emite Gestión.</td>
                <td class="r">Generada el {{ $generatedAt }}@if ($generatedBy) por {{ $generatedBy }}@endif</td>
            </tr>
        </table>
    </div>

    <table class="head">
        <tr>
            <td>
                <div class="brand">{{ $company['name'] ?? 'Empresa' }}</div>
                @if ($company['cif'])<div class="muted">CIF {{ $company['cif'] }}</div>@endif
                @if ($company['address'])<div class="muted small">{{ $company['address'] }}</div>@endif
                @if ($company['city_line'])<div class="muted small">{{ $company['city_line'] }}@if ($company['country']) · {{ $company['country'] }}@endif</div>@endif
            </td>
            <td class="doc-box">
                <div class="doc-kind">{{ $doc['simplified'] ? 'Factura simplificada' : 'Factura' }}</div>
                <div class="doc-ref">{{ $doc['ref'] }}</div>
                <div class="copy-tag">COPIA INFORMATIVA</div>
                @if ($doc['void'])<div class="void-tag">ANULADA</div>@endif
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <div class="lbl">Emisor</div>
                <div class="party-name">{{ $company['name'] ?? '—' }}</div>
                @if ($company['cif'])<div>CIF {{ $company['cif'] }}</div>@endif
                @if ($company['address'])<div>{{ $company['address'] }}</div>@endif
                @if ($company['city_line'])<div>{{ $company['city_line'] }}</div>@endif
                @if ($company['country'])<div>{{ $company['country'] }}</div>@endif
            </td>
            <td class="gap"></td>
            <td>
                <div class="lbl">Cliente</div>
                <div class="party-name">{{ $customer['name'] ?? '—' }}</div>
                @if ($customer['cif'])<div>NIF {{ $customer['cif'] }}</div>@endif
                @if ($customer['address'])<div>{{ $customer['address'] }}</div>@endif
                @if ($customer['city_line'])<div>{{ $customer['city_line'] }}</div>@endif
                @if ($customer['country'])<div>{{ $customer['country'] }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td><div class="k">Serie</div><div class="v">{{ $doc['series'] ?? '—' }}</div></td>
            <td><div class="k">Número</div><div class="v">{{ $doc['number'] ?? '—' }}</div></td>
            <td><div class="k">Año</div><div class="v">{{ $doc['year'] ?? '—' }}</div></td>
            <td><div class="k">Fecha</div><div class="v">{{ $doc['date'] ?? '—' }}</div></td>
            <td><div class="k">Forma de pago</div><div class="v">{{ $doc['payment_method'] ?? '—' }}</div></td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="num">Uds.</th>
                <th class="num">Precio</th>
                <th class="num">Dto.</th>
                <th class="num">IVA</th>
                <th class="num">Base</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($doc['lines'] as $line)
                <tr>
                    <td>
                        {{ $line['description'] }}
                        @if ($line['code'])<div class="code">{{ $line['code'] }}</div>@endif
                    </td>
                    <td class="num">{{ R::units($line['units']) }}</td>
                    <td class="num">{{ R::price($line['price']) }}</td>
                    <td class="num">{{ $line['discount'] > 0 ? R::percent($line['discount']) : '—' }}</td>
                    <td class="num">{{ R::percent($line['tax']) }}@if ($line['surcharge'] > 0) + {{ R::percent($line['surcharge']) }} RE @endif</td>
                    <td class="num">{{ R::money($line['base']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">La factura no tiene líneas en Gestión.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="sums">
        <tr>
            <td width="58%">
                @if (count($doc['taxes']))
                    <table class="taxes">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Base imponible</th>
                                <th>Cuota IVA</th>
                                @if (collect($doc['taxes'])->contains(fn ($t) => $t['surcharge'] > 0))<th>Recargo</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($doc['taxes'] as $t)
                                <tr>
                                    <td>IVA {{ R::percent($t['tax']) }}@if ($t['surcharge'] > 0) + RE {{ R::percent($t['surcharge']) }}@endif</td>
                                    <td class="num">{{ R::money($t['base']) }}</td>
                                    <td class="num">{{ R::money($t['tax_amount']) }}</td>
                                    @if (collect($doc['taxes'])->contains(fn ($x) => $x['surcharge'] > 0))<td class="num">{{ R::money($t['surcharge_amount']) }}</td>@endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
            <td width="4%"></td>
            <td width="38%">
                <table class="total">
                    <tr><td>Base imponible</td><td class="num">{{ R::money($doc['totals']['base']) }}</td></tr>
                    <tr><td>Impuestos</td><td class="num">{{ R::money($doc['totals']['taxes']) }}</td></tr>
                    <tr class="grand"><td>Total</td><td class="num">{{ R::money($doc['totals']['total']) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($doc['observations'])
        <div class="obs">
            <div class="lbl">Observaciones</div>
            <div class="obs-v">{{ $doc['observations'] }}</div>
        </div>
    @endif

    <div class="notice">
        Este documento es una copia informativa generada a partir de los datos de Gestión para atención al cliente.
        No sustituye a la factura original ni tiene validez fiscal. Si necesita la factura, solicítela y se la enviaremos desde Gestión.
    </div>
</body>
</html>
