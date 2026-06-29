<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis preferencias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5" style="max-width:680px">
        <h2>Mis preferencias</h2>
        <p class="text-muted">{{ $subscriber->email }}</p>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="post" action="{{ route('campaign.manage.update', $subscriber->uid) }}">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="first_name" value="{{ $subscriber->first_name }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellido</label>
                    <input type="text" name="last_name" value="{{ $subscriber->last_name }}" class="form-control">
                </div>
            </div>

            <h5>Listas</h5>
            <table class="table">
                <thead><tr><th>Lista</th><th>Suscripción</th></tr></thead>
                <tbody>
                    @foreach ($lists as $l)
                        <tr>
                            <td>{{ $l->name }}</td>
                            <td>
                                <select name="lists[{{ $l->uid }}]" class="form-select form-select-sm">
                                    <option value="subscribed" @selected($l->status === 'subscribed')>Suscrito</option>
                                    <option value="unsubscribed" @selected($l->status === 'unsubscribed')>No deseo recibir más</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button type="submit" class="btn btn-primary">Guardar preferencias</button>
        </form>
    </div>
</body>
</html>
