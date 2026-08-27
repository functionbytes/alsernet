<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    {!! $fontFaceCss !!}

    @page { margin: 0; }
    body { margin: 0; padding: 0; }
    .page {
        position: relative;
        width: {{ $pageWidthMm }}mm;
        height: {{ $pageHeightMm }}mm;
        page-break-after: always;
    }
    .page:last-child { page-break-after: auto; }
    .field {
        position: absolute;
        overflow: hidden;
    }
    /* El texto se centra en los dos ejes DENTRO de la caja configurada, no
       respecto a la pagina: una tabla al 100% de la caja con la celda en
       vertical-align:middle es la unica forma fiable de centrado vertical en
       DomPDF (no soporta flexbox ni el truco de line-height con varias lineas). */
    .field table {
        width: 100%;
        border-collapse: collapse;
    }
    .field td {
        padding: 0;
        text-align: center;
        vertical-align: middle;
        /* El interlineado lo fija cada campo: cuando el mensaje no cabe se
           aprieta antes de encoger la letra. */
        line-height: 1.2;
        /* Una URL o una palabra kilometrica se parte en vez de salirse por el
           lateral de la caja. */
        word-wrap: break-word;
    }
    .field img {
        vertical-align: middle;
    }
</style>
</head>
<body>
@foreach ($pages as $page)
    {{-- Sin imagen de fondo: el sobre y la tarjeta ya vienen impresos de
         imprenta y aqui solo se deposita el texto. La pagina conserva el tamano
         real de la pieza, asi que las cajas caen en el mismo sitio que en el
         editor de ajustes. --}}
    <div class="page">
        <div class="field" style="
            left: {{ $page['t1']['left'] }}mm;
            top: {{ $page['t1']['top'] }}mm;
            width: {{ $page['t1']['width'] }}mm;
            height: {{ $page['t1']['height'] }}mm;
            font-family: {{ $page['t1']['font_family'] }};
            font-size: {{ $page['t1']['font_size'] }}pt;
            line-height: {{ $page['t1']['line_height'] }};
            color: {{ $page['t1']['color'] }};
            opacity: {{ $page['t1']['opacity'] }};
        ">
            <table>
                <tr><td style="height: {{ $page['t1']['height'] }}mm;">{!! $page['t1']['html'] !!}</td></tr>
            </table>
        </div>

        <div class="field" style="
            left: {{ $page['t2']['left'] }}mm;
            top: {{ $page['t2']['top'] }}mm;
            width: {{ $page['t2']['width'] }}mm;
            height: {{ $page['t2']['height'] }}mm;
            font-family: {{ $page['t2']['font_family'] }};
            font-size: {{ $page['t2']['font_size'] }}pt;
            line-height: {{ $page['t2']['line_height'] }};
            color: {{ $page['t2']['color'] }};
            opacity: {{ $page['t2']['opacity'] }};
        ">
            <table>
                <tr><td style="height: {{ $page['t2']['height'] }}mm;">{{ $page['t2']['text'] }}</td></tr>
            </table>
        </div>
    </div>
@endforeach
</body>
</html>
