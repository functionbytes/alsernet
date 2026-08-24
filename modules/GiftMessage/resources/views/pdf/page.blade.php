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
    .bg {
        position: absolute;
        left: 0;
        top: 0;
        width: {{ $pageWidthMm }}mm;
        height: {{ $pageHeightMm }}mm;
    }
    .field {
        position: absolute;
        overflow: hidden;
        text-align: center;
        line-height: 1.2;
    }
    .field img {
        vertical-align: middle;
    }
</style>
</head>
<body>
@foreach ($pages as $page)
    <div class="page">
        @if ($backgroundPath)
            <img class="bg" src="{{ $backgroundPath }}">
        @endif

        <div class="field" style="
            left: {{ $page['t1']['left'] }}mm;
            top: {{ $page['t1']['top'] }}mm;
            width: {{ $page['t1']['width'] }}mm;
            height: {{ $page['t1']['height'] }}mm;
            font-family: {{ $page['t1']['font_family'] }};
            font-size: {{ $page['t1']['font_size'] }}pt;
            color: {{ $page['t1']['color'] }};
            opacity: {{ $page['t1']['opacity'] }};
        ">{!! $page['t1']['html'] !!}</div>

        <div class="field" style="
            left: {{ $page['t2']['left'] }}mm;
            top: {{ $page['t2']['top'] }}mm;
            width: {{ $page['t2']['width'] }}mm;
            height: {{ $page['t2']['height'] }}mm;
            font-family: {{ $page['t2']['font_family'] }};
            font-size: {{ $page['t2']['font_size'] }}pt;
            color: {{ $page['t2']['color'] }};
            opacity: {{ $page['t2']['opacity'] }};
        ">{{ $page['t2']['text'] }}</div>
    </div>
@endforeach
</body>
</html>
