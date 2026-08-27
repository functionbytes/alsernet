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
        text-align: center;
        word-wrap: break-word;
    }
</style>
</head>
<body>
@foreach ($pages as $slots)
    <div class="page">
        @if ($backgroundPath)
            <img class="bg" src="{{ $backgroundPath }}">
        @endif

        @foreach ($slots as $elements)
            @foreach ($elements as $el)
                @if (! empty($el['image_src']))
                    <img class="field" src="{{ $el['image_src'] }}" style="
                        left: {{ $el['left'] }}mm;
                        top: {{ $el['top'] }}mm;
                        width: {{ $el['width'] }}mm;
                        height: {{ $el['height'] }}mm;
                    ">
                @else
                    <div class="field" style="
                        left: {{ $el['left'] }}mm;
                        top: {{ $el['top'] }}mm;
                        width: {{ $el['width'] }}mm;
                        color: {{ $el['color'] }};
                        font-family: {{ $el['font_family'] }};
                        font-size: {{ $el['font_size'] }}pt;
                        font-weight: {{ $el['bold'] ? 'bold' : 'normal' }};
                        font-style: {{ $el['italic'] ? 'italic' : 'normal' }};
                    ">{{ $el['text'] }}</div>
                @endif
            @endforeach
        @endforeach
    </div>
@endforeach
</body>
</html>
