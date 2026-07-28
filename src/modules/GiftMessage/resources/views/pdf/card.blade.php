<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
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
        white-space: nowrap;
    }
    .field-t1 {
        left: 0;
        width: {{ $pageWidthMm }}mm;
        text-align: center;
        white-space: normal;
        line-height: 1.2;
    }
    .field-t1 img {
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

        <div class="field field-t1" style="
            top: {{ $page['t1']['top'] }}mm;
            font-family: {{ $page['t1']['font_family'] }};
            font-size: {{ $page['t1']['font_size'] }}pt;
        ">{!! $page['t1']['html'] !!}</div>

        <div class="field" style="
            left: {{ $page['t2']['left'] }}mm;
            top: {{ $page['t2']['top'] }}mm;
            font-family: {{ $page['t2']['font_family'] }};
            font-size: {{ $page['t2']['font_size'] }}pt;
        ">{{ $page['t2']['text'] }}</div>
    </div>
@endforeach
</body>
</html>
