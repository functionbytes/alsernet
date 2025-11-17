<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de documentos</title>
    <style>
        @page { margin: 0; size: A4; }
        html, body {
            margin: 0;
            padding: 0;
        }
        .page {
            width: 210mm;
            height: 297mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .image-preview {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>

@foreach($documents as $label => $files)
    @foreach($files as $file)
        @if(Str::startsWith($file['file']->mime_type, 'image/'))
            @php
                $base64 = null;
                $path = $file['file']->getPath();
                if (file_exists($path) && is_readable($path)) {
                    try {
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $data = @file_get_contents($path);
                        if ($data) {
                            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        }
                    } catch (\Exception $e) {
                        $base64 = null;
                    }
                }
            @endphp
            @if($base64)
                <div class="page" @if(!($loop->last && $loop->parent->last)) style="page-break-after: always;" @endif>
                    <img src="{{ $base64 }}" class="image-preview" alt="Documento escaneado">
                </div>
            @endif
        @endif
    @endforeach
@endforeach

</body>
</html>
