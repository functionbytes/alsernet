<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suscríbete a {{ $list->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5" style="max-width:560px">
        <h2>{{ $list->name }}</h2>
        <p class="text-muted">{{ $list->description }}</p>

        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="post" action="{{ route('campaign.subscribe.submit', $list->uid) }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            @foreach ($fields as $field)
                <div class="mb-3">
                    <label class="form-label">{{ $field->label }}{{ $field->required ? ' *' : '' }}</label>
                    @if ($field->type === 'text' || $field->type === 'email')
                        <input type="{{ $field->type }}" name="{{ strtolower($field->tag) }}" class="form-control" {{ $field->required ? 'required' : '' }}>
                    @elseif ($field->type === 'select')
                        <select name="{{ strtolower($field->tag) }}" class="form-select">
                            @foreach ($field->options ?? [] as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary w-100">Suscribirme</button>
        </form>
    </div>
</body>
</html>
