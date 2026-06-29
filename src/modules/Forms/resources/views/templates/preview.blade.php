<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $template['name'] ?? 'Plantilla' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
</head>
<body class="bg-light py-4">
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">{{ $template['name'] ?? 'Plantilla' }}</h5>
            @if(!empty($template['description']))
                <p class="text-muted mb-0 mt-1">{{ $template['description'] }}</p>
            @endif
        </div>
        <div class="card-body">
            @foreach($template['fields'] ?? [] as $field)
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        {{ $field['label'] ?? $field['name'] ?? '' }}
                        @if(!empty($field['is_required']))<span class="text-danger">*</span>@endif
                    </label>
                    @php $type = $field['type'] ?? 'text'; @endphp
                    @if($type === 'textarea')
                        <textarea class="form-control" rows="3" placeholder="{{ $field['placeholder'] ?? '' }}" disabled></textarea>
                    @elseif($type === 'select')
                        <select class="form-select" disabled>
                            <option>Selecciona una opción</option>
                            @foreach($field['options'] ?? [] as $opt)
                                <option>{{ is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? $opt) : $opt }}</option>
                            @endforeach
                        </select>
                    @elseif(in_array($type, ['section_header', 'html_block', 'divider']))
                        <hr>
                    @else
                        <input type="{{ $type }}" class="form-control" placeholder="{{ $field['placeholder'] ?? '' }}" disabled>
                    @endif
                </div>
            @endforeach
            <button class="btn btn-primary" disabled>Enviar</button>
        </div>
    </div>
</div>
</body>
</html>
